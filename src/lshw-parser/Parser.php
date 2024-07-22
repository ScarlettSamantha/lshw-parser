<?php
/**
 * Parser.php
 *
 * This file contains the Parser class which parses the lshw XML output.
 *
 * PHP version 8.2
 *
 * @category  Parsing
 * @package   Scarlett\LshwParser
 * @license   MIT License
 * @author    Scarlett Samantha Verheul <scarlett.verheul@gmail.com>
 * @link      https://scarlettbytes.nl/lshw-parser
 */
declare(strict_types=1);

namespace Scarlett\LshwParser;

use DOMDocument;
use DOMXPath;
use DOMElement;
use Scarlett\LshwParser\Entry;
use Scarlett\LshwParser\Exceptions\ParserException;

/**
 * Class Parser
 */
class Parser
{
    private const HUB_PATTERNS = [
        '/usb\s*(hub|2(\.0)?\s*hub|3(\.0)?\s*hub)/i',
        '/(pci(e)?\s*bridge)/i',
        '/(isa\s*bridge)/i',
        '/hub/i',
    ];

    private DOMDocument $dom;
    private bool $skipHubs;

    /**
     * Constructor to initialize DOMDocument and handle XML content.
     * 
     * @param string $xmlContent The XML content or path to XML file.
     * @param bool $skipHubs Flag to determine if hubs should be skipped.
     * @throws ParserException If file cannot be read or XML content is invalid.
     */
    public function __construct(string $xmlContent, bool $skipHubs = false)
    {
        $this->dom = new DOMDocument();
        $this->skipHubs = $skipHubs;

        if (is_file($xmlContent) && is_readable($xmlContent)) {
            $fileXmlContent = file_get_contents($xmlContent);
            if ($fileXmlContent === false) {
                throw new ParserException("Unable to read file: $xmlContent");
            }
            $xmlContent = $fileXmlContent;
        } elseif (file_exists($xmlContent)) {
            throw new ParserException("File exists but is not readable: $xmlContent");
        }
        $this->loadXML($xmlContent);
    }


    /**
     * Loads XML content into the DOMDocument.
     *
     * @param string $xmlContent The XML content to load.
     * @throws ParserException If the XML content is invalid.
     */
    private function loadXML(string $xmlContent): void
    {
        if (!$this->dom->loadXML($xmlContent, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            throw new ParserException("Invalid XML content provided");
        }
    }

    /**
     * Gets the skipHubs property.
     *
     * @return bool Whether hub nodes are skipped.
     */
    public function getSkipHubs(): bool
    {
        return $this->skipHubs;
    }

    /**
     * Sets the skipHubs property.
     *
     * @param bool $skipHubs Whether to skip hub nodes.
     */
    public function setSkipHubs(bool $skipHubs): void
    {
        $this->skipHubs = $skipHubs;
    }

    /**
     * Gets system memory information.
     *
     * @return Entry|array The system memory information, or null if not found.
     */
    public function getSystemMemory(): Entry|array
    {
        return $this->searchNodesByClass('memory');
    }

    /**
     * Gets CPU information.
     *
     * @return Entry|array The CPU information, or null if not found.
     */
    public function getCpuInfo(): Entry|array
    {
        return $this->searchNodesByClass('processor');
    }

    /**
     * Gets storage device information.
     *
     * @return Entry|array An array of LshwEntry objects for each storage device.
     */
    public function getStorageDevices(): Entry|array
    {
        return $this->searchNodesByClass('disk');
    }

    /**
     * Gets network interface information.
     *
     * @return Entry|array An array of LshwEntry objects for each network interface.
     */
    public function getNetworkInterfaces(): Entry|array
    {
        return $this->searchNodesByClass('network');
    }

    /**
     * Searches for a single node by its class.
     *
     * @param string $class The class of the node to search for.
     * @return Entry|null The matched LshwEntry, or null if not found.
     */
    private function searchNodeByClass(string $class): ?Entry
    {
        $xpath = new DOMXPath($this->dom);
        $entries = $xpath->query("//node[@class='$class']");

        if ($entries === false || $entries->length === 0) {
            return null;
        }

        return new Entry($entries->item(0));
    }

    /**
     * Searches for multiple nodes by their class.
     *
     * @param string $class The class of the nodes to search for.
     * @return array An array of LshwEntry objects for each matched node.
     * @throws ParserException If the XPath query is invalid.
     */
    private function searchNodesByClass(string $class): array
    {
        $xpath = new DOMXPath($this->dom);
        $entries = $xpath->query("//node[@class='$class']");

        if ($entries === false) {
            throw new ParserException("Invalid XPath query provided");
        }

        $results = [];
        foreach ($entries as $entry) {
            if ($this->skipHubs && $this->isHub($entry)) {
                continue;
            }
            $results[] = new Entry($entry);
        }

        return $results;
    }

    /**
     * Searches nodes using a custom filter function.
     *
     * @param callable $filter A filter function that receives an array of properties and returns true for a match, false otherwise.
     * @return array An array of matched LshwEntry objects.
     * @throws ParserException If the XPath query is invalid.
     */
    public function searchNodesByFilter(callable $filter): array
    {
        $xpath = new DOMXPath($this->dom);
        $entries = $xpath->query("//node");

        if ($entries === false) {
            throw new ParserException("Invalid XPath query provided");
        }

        $results = [];
        foreach ($entries as $entry) {
            if ($this->skipHubs && $this->isHub($entry)) {
                continue;
            }
            $properties = $this->getNodeProperties($entry);
            if ($filter($properties)) {
                $results[] = new Entry($entry);
            }
        }

        return $results;
    }

    /**
     * Searches nodes by given properties with logical operator.
     *
     * @param array $properties The properties to search by.
     * @param string $logic The logical operator ('and' or 'or').
     * @return array An array of matched LshwEntry objects.
     * @throws ParserException If the XPath query is invalid.
     */
    public function parseByProperties(array $properties, string $logic = 'and'): array
    {
        return $this->searchNodesByFilter(function ($nodeProperties) use ($properties, $logic) {
            $matches = [];
            foreach ($properties as $key => $value) {
                if (is_array($value)) {
                    $matches[] = in_array($nodeProperties[$key] ?? null, $value);
                } else {
                    $matches[] = ($nodeProperties[$key] ?? null) === $value;
                }
            }
            return $logic === 'and' ? !in_array(false, $matches) : in_array(true, $matches);
        });
    }

    /**
     * Gets properties of a DOM node as an associative array.
     *
     * @param DOMElement $node The DOM node to extract properties from.
     * @return array An associative array of properties.
     */
    private function getNodeProperties(DOMElement $node): array
    {
        $properties = [];
        foreach ($node->attributes as $attr) {
            $properties[$attr->name] = $attr->value;
        }
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $properties[$child->nodeName] = $child->nodeValue;
            }
        }
        return $properties;
    }

    /**
     * Checks if a node matches any of the hub patterns.
     *
     * @param DOMElement $node The DOM node to check.
     * @return bool True if the node is considered a hub, false otherwise.
     */
    private function isHub(DOMElement $node): bool
    {
        $description = $node->getElementsByTagName('description')->item(0)->nodeValue ?? '';
        foreach (self::HUB_PATTERNS as $pattern) {
            if (preg_match($pattern, $description)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Provides access to the raw DOM document.
     *
     * @return DOMDocument The raw DOM document.
     */
    public function getRawDomDocument(): DOMDocument
    {
        return $this->dom;
    }

    /**
     * Searches nodes using a custom XPath query.
     *
     * @param string $xpathQuery The XPath query string.
     * @return array An array of matched LshwEntry objects.
     * @throws ParserException If the XPath query is invalid.
     */
    public function searchNodesByXPath(string $xpathQuery): array
    {
        $xpath = new DOMXPath($this->dom);
        $entries = $xpath->query($xpathQuery);

        if ($entries === false) {
            throw new ParserException("Invalid XPath query provided");
        }

        $results = [];
        foreach ($entries as $entry) {
            if ($this->skipHubs && $this->isHub($entry)) {
                continue;
            }
            $results[] = new Entry($entry);
        }

        return $results;
    }
}
