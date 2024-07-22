<?php
/**
 * EntryTest.php
 *
 * This file contains the EntryTest class which tests the functionality of the Entry class.
 *
 * PHP version 8.2
 *
 * @category  Testing
 * @package   Scarlett\LshwParser\Tests
 * @author    Scarlett Samantha Verheul <scarlett.verheul@gmail.com>
 * @license   MIT License
 * @link      https://scarlettbytes.nl/lshw-parser
 */
declare(strict_types=1);

namespace Scarlett\LshwParser\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Scarlett\LshwParser\Entry;
use Scarlett\LshwParser\Property;
use DOMDocument;
use ReflectionClass;

#[CoversClass(Entry::class)]
#[UsesClass(Property::class)]
final class EntryTest extends TestCase
{
    private Entry $entry;

    protected function setUp(): void
    {
        $xml = <<<XML
<node>
    <vendor>GenuineIntel</vendor>
    <product>Core i7</product>
    <description>Intel(R) Core(TM) i7 CPU</description>
</node>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $this->entry = new Entry($doc->documentElement);
    }

    public function testGetProperty(): void
    {
        $property = $this->entry->getProperty('vendor');
        $this->assertInstanceOf(Property::class, $property);
        $this->assertEquals('GenuineIntel', $property->getFirstValue());
    }

    public function testGetProperties(): void
    {
        $properties = $this->entry->getProperties();
        $this->assertCount(3, $properties);
    }

    public function testHasProperty(): void
    {
        $this->assertTrue($this->entry->hasProperty('vendor'));
        $this->assertFalse($this->entry->hasProperty('nonexistent'));
    }

    public function testIterator(): void
    {
        $properties = [];
        foreach ($this->entry as $key => $value) {
            $properties[$key] = $value;
        }
        $this->assertArrayHasKey('vendor', $properties);
        $this->assertEquals('GenuineIntel', $properties['vendor']);
    }

    private function createDomElementWithChildren(array $childNodes): \DOMElement
    {
        $doc = new DOMDocument();
        $parent = $doc->createElement('parent');
        foreach ($childNodes as $nodeName => $nodeValue) {
            $child = $doc->createElement($nodeName, $nodeValue);
            $parent->appendChild($child);
        }
        return $parent;
    }


}
