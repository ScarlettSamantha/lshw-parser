<?php
/**
 * Entry.php
 *
 * This file contains the Entry class which represents an entry in the lshw XML output.
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

use DOMNode;
use Iterator;

class Entry implements Iterator
{
    private array $properties;
    private int $position = 0;
    private array $keys = [];

    /**
     * Entry constructor.
     *
     * @param DOMNode $node The DOM node to parse.
     */
    public function __construct(DOMNode $node)
    {
        $this->properties = $this->parseNode($node);
        $this->keys = array_keys($this->properties);
    }

    /**
     * Parses a DOM node to extract its properties.
     *
     * @param DOMNode $node The DOM node to parse.
     * @return array An associative array of properties.
     */
    private function parseNode(DOMNode $node): array
    {
        $properties = [];
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                // If the child node already exists, convert to array and append new value
                if (isset($properties[$child->nodeName])) {
                    if (is_array($properties[$child->nodeName])) {
                        $properties[$child->nodeName][] = $child->nodeValue;
                    } else {
                        $properties[$child->nodeName] = [$properties[$child->nodeName], $child->nodeValue];
                    }
                } else {
                    $properties[$child->nodeName] = $child->nodeValue;
                }
            }
        }
        return $properties;
    }

    /**
     * Gets a specific property by name.
     *
     * @param string $property The property name to retrieve.
     * @param bool $asArray Whether to return the property as an array if it has multiple values.
     * @return Property|null The property wrapper, or null if not found.
     */
    public function getProperty(string $property, bool $asArray = false): ?Property
    {
        if (!isset($this->properties[$property])) {
            return null;
        }

        $value = $this->properties[$property];
        if ($asArray) {
            return new Property(is_array($value) ? $value : [$value]);
        }

        return new Property(is_array($value) ? $value[0] : $value);
    }

    /**
     * Gets all properties.
     *
     * @return array An associative array of all properties.
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Checks if a property exists.
     *
     * @param string $property The property name to check.
     * @return bool True if the property exists, false otherwise.
     */
    public function hasProperty(string $property): bool
    {
        return isset($this->properties[$property]);
    }

    /**
     * Return the current element
     *
     * @return mixed Can return any type.
     */
    public function current(): mixed
    {
        return $this->properties[$this->keys[$this->position]];
    }

    /**
     * Move forward to next element
     *
     * @return void
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * Return the key of the current element
     *
     * @return mixed scalar on success, or null on failure.
     */
    public function key(): mixed
    {
        return $this->keys[$this->position];
    }

    /**
     * Checks if current position is valid
     *
     * @return bool The return value will be casted to boolean and then evaluated.
     */
    public function valid(): bool
    {
        return isset($this->keys[$this->position]);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->position = 0;
    }
}
