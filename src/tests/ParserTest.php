<?php
/**
 * ParserTest.php
 *
 * This file contains the ParserTest class which tests the functionality of the Parser class.
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
use Scarlett\LshwParser\Parser;
use Scarlett\LshwParser\Entry;
use Scarlett\LshwParser\Property;
use Scarlett\LshwParser\Exceptions\ParserException;
use ReflectionClass;
use DOMDocument;

#[CoversClass(Parser::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Property::class)]
#[UsesClass(ParserException::class)]
final class ParserTest extends TestCase
{
    private string $xmlContent;

    protected function setUp(): void
    {
        $this->xmlContent = file_get_contents(__DIR__ . '/fixtures/lshw-output.xml');
    }

    public function testConstructorWithXmlContent(): void
    {
        // Test the constructor with XML content as string
        $parser = new Parser($this->xmlContent);
        $this->assertInstanceOf(Parser::class, $parser);
    }

    public function testConstructorWithXmlFile(): void
    {
        // Test the constructor with XML content from a file
        $filePath = __DIR__ . '/fixtures/lshw-output.xml';
        $parser = new Parser($filePath);
        $this->assertInstanceOf(Parser::class, $parser);
    }

    public function testConstructorWithNonExistentFile(): void
    {
        // Test the constructor with a non-existent file path
        $this->expectException(ParserException::class);
        new Parser('/non/existent/file/path.xml');
    }

    public function testConstructorWithUnreadableFile(): void
    {
        // Test the constructor with an unreadable file path
        $unreadableFilePath = __DIR__ . '/fixtures/unreadable-file.xml';

        // Create an unreadable file
        file_put_contents($unreadableFilePath, 'Unreadable content');
        chmod($unreadableFilePath, 0000);

        try {
            $this->expectException(ParserException::class);
            new Parser($unreadableFilePath);
        } finally {
            // Cleanup by removing the unreadable file
            chmod($unreadableFilePath, 0644);
            unlink($unreadableFilePath);
        }
    }

    public function testGetSystemMemory(): void
    {
        $parser = new Parser($this->xmlContent);
        $memory = $parser->getSystemMemory();
        $this->assertIsArray($memory);
        $this->assertCount(15, $memory);
        $this->assertEquals('BIOS', $memory[0]->getProperty('description')->getFirstValue());
    }

    public function testSearchNodeByClass(): void
    {
        $parser = new Parser($this->xmlContent);

        $reflection = new ReflectionClass($parser);
        $method = $reflection->getMethod('searchNodeByClass');
        $method->setAccessible(true);

        $entry = $method->invokeArgs($parser, ['processor']);
        $this->assertInstanceOf(Entry::class, $entry);
        $this->assertEquals('CPU', $entry->getProperty('description')->getFirstValue());
        $this->assertEquals('Intel Corp.', $entry->getProperty('vendor')->getFirstValue());
        $this->assertEquals('13th Gen Intel(R) Core(TM) i5-13600K', $entry->getProperty('product')->getFirstValue());

    }

    public function testSarchNodeByNonExistantClass(): void
    {
        $parser = new Parser($this->xmlContent);

        $reflection = new ReflectionClass($parser);
        $method = $reflection->getMethod('searchNodeByClass');
        $method->setAccessible(true);

        $entry = $method->invokeArgs($parser, ['nonexistent']);
        $this->assertNull($entry);
    }

    public function testGetCpuInfo(): void
    {
        $parser = new Parser($this->xmlContent);
        $cpuInfo = $parser->getCpuInfo();
        $this->assertInstanceOf(Entry::class, $cpuInfo[0]);
        $this->assertEquals('CPU', $cpuInfo[0]->getProperty('description')->getFirstValue());
        $this->assertEquals('Intel Corp.', $cpuInfo[0]->getProperty('vendor')->getFirstValue());
    }

    public function testGetStorageDevices(): void
    {
        $parser = new Parser($this->xmlContent);
        $storageDevices = $parser->getStorageDevices();
        $this->assertIsArray($storageDevices);
        $this->assertNotEmpty($storageDevices);
        foreach ($storageDevices as $device) {
            $this->assertInstanceOf(Entry::class, $device);
        }
        $this->assertEquals('NVMe disk', $storageDevices[0]->getProperty('description')->getFirstValue());
        $this->assertEquals('hwmon2', $storageDevices[0]->getProperty('logicalname')->getFirstValue());
        $this->assertEquals('196608', $storageDevices[6]->getProperty('size')->getFirstValue());
        $this->assertEquals('Crucial_CT525MX3', $storageDevices[7]->getProperty('product')->getFirstValue());
        $this->assertEquals('scsi@4:0.0.0', $storageDevices[8]->getProperty('businfo')->getFirstValue());
    }

    public function testGetNetworkInterfaces(): void
    {
        $parser = new Parser($this->xmlContent);
        $networkInterfaces = $parser->getNetworkInterfaces();
        $this->assertIsArray($networkInterfaces);
        $this->assertNotEmpty($networkInterfaces);
        $this->assertInstanceOf(Entry::class, $networkInterfaces[0]);

        // Key: 0
        $this->assertEquals('Wireless interface', $networkInterfaces[0]->getProperty('description')->getFirstValue());
        $this->assertEquals('Raptor Lake-S PCH CNVi WiFi', $networkInterfaces[0]->getProperty('product')->getFirstValue());
        $this->assertEquals('Intel Corporation', $networkInterfaces[0]->getProperty('vendor')->getFirstValue());

        // Key: 1
        $this->assertEquals('Ethernet interface', $networkInterfaces[1]->getProperty('description')->getFirstValue());
        $this->assertEquals('Ethernet Controller I225-V', $networkInterfaces[1]->getProperty('product')->getFirstValue());
        $this->assertEquals('Intel Corporation', $networkInterfaces[1]->getProperty('vendor')->getFirstValue());

        // Key: 2
        $this->assertEquals('Ethernet interface', $networkInterfaces[2]->getProperty('description')->getFirstValue());
        $this->assertEquals('82599 10 Gigabit Network Connection', $networkInterfaces[2]->getProperty('product')->getFirstValue());
        $this->assertEquals('Intel Corporation', $networkInterfaces[2]->getProperty('vendor')->getFirstValue());
    }


    public function testParseByPropertiesWithAndLogic(): void
    {
        $parser = new Parser($this->xmlContent);
        $results = $parser->parseByProperties(['class' => 'processor', 'vendor' => 'Intel Corp.'], 'and');
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertInstanceOf(Entry::class, $results[0]);
        $this->assertEquals('Intel Corp.', $results[0]->getProperty('vendor')->getFirstValue());
    }

    public function testParseByPropertiesWithOrLogic(): void
    {
        $parser = new Parser($this->xmlContent);
        $results = $parser->parseByProperties(['class' => 'NPU', 'vendor' => 'Micro-Star International Co., Ltd.'], 'or');
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertInstanceOf(Entry::class, $results[0]);
        $this->assertEquals('Micro-Star International Co., Ltd.', $results[0]->getProperty('vendor')->getFirstValue());
        $this->assertEquals('MS-7E06', $results[0]->getProperty('product')->getFirstValue());
        $this->assertEquals('Micro-Star International Co., Ltd.', $results[1]->getProperty('vendor')->getFirstValue());
        $this->assertEquals('PRO Z790-P WIFI (MS-7E06)', $results[1]->getProperty('product')->getFirstValue());
    }


    public function testParseByPropertiesWithArrayValuesAndLogic(): void
    {
        $parser = new Parser($this->xmlContent);
        $results = $parser->parseByProperties(['class' => 'processor', 'vendor' => ['Intel Corp.', 'AMD']], 'and');
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertIsArray($results);
        $this->assertInstanceOf(Entry::class, $results[0]);
        $this->assertEquals('Intel Corp.', $results[0]->getProperty('vendor')->getFirstValue());
    }


    public function testParserUnknownProperty(): void
    {
        $parser = new Parser($this->xmlContent);
        $results = $parser->parseByProperties(['class' => 'processor', 'unknown' => 'Intel Corp.'], 'and');
        $this->assertIsArray($results);
        $this->assertCount(0, $results);
    }

    public function testParseByPropertiesWithArrayValuesOrLogic(): void
    {
        $parser = new Parser($this->xmlContent);
        $results = $parser->parseByProperties(['class' => 'processor', 'vendor' => ['Intel Corp.', 'AMD']], 'or');
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertInstanceOf(Entry::class, $results[0]);
        $this->assertInstanceOf(Entry::class, $results[1]);
        $this->assertEquals('Intel Corp.', $results[0]->getProperty('vendor')->getFirstValue());
        $this->assertEquals("13th Gen Intel(R) Core(TM) i5-13600K", $results[0]->getProperty('product')->getFirstValue());
        $this->assertEquals('Intel Corp.', $results[1]->getProperty('vendor')->getFirstValue());
        $this->assertEquals('AX211 Bluetooth', $results[1]->getProperty('product')->getFirstValue());
    }


    public function testGetSpecificNodeAttributes(): void
    {
        $parser = new Parser($this->xmlContent);
        $results = $parser->parseByProperties(['id' => 'cpu']);
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $cpu = $results[0];
        $this->assertEquals('13th Gen Intel(R) Core(TM) i5-13600K', $cpu->getProperty('product')->getFirstValue());
        $this->assertEquals('Intel Corp.', $cpu->getProperty('vendor')->getFirstValue());
        $this->assertEquals('5098379000', $cpu->getProperty('size')->getFirstValue());
    }


    public function testSearchNodesByFilterWithCallable(): void
    {
        $parser = new Parser($this->xmlContent);
        $results = $parser->searchNodesByFilter(function ($properties) {
            return isset($properties['businfo']) && $properties['businfo'] === 'usb@1:5.2.1.3';
        });
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(Entry::class, $result);
            $this->assertEquals('Logitech, Inc.', $result->getProperty('vendor')->getFirstValue());
        }
    }


    public function testGetRawDomDocument(): void
    {
        $parser = new Parser($this->xmlContent);
        $domDocument = $parser->getRawDomDocument();
        $this->assertInstanceOf(DOMDocument::class, $domDocument);
        $this->assertNotEmpty($domDocument->documentElement);
    }


    public function testSearchNodesByXPath(): void
    {
        $parser = new Parser($this->xmlContent);
        $results = $parser->searchNodesByXPath("//node[@class='processor']");
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(Entry::class, $result);
            $this->assertEquals('CPU', $result->getProperty('description')->getFirstValue());
        }
    }

    public function testIsHub(): void
    {
        $parser = new Parser($this->xmlContent);

        $reflection = new ReflectionClass($parser);
        $method = $reflection->getMethod('isHub');
        $method->setAccessible(true);

        // Creating a DOMElement for testing
        $doc = new DOMDocument();
        $xml = <<<XML
<node>
    <description>USB hub</description>
</node>
XML;
        $doc->loadXML($xml);
        $node = $doc->documentElement;

        // Test with a description that matches a hub pattern
        $isHub = $method->invokeArgs($parser, [$node]);
        $this->assertTrue($isHub);

        // Test with a description that does not match a hub pattern
        $xml = <<<XML
<node>
    <description>Non-matching device</description>
</node>
XML;
        $doc->loadXML($xml);
        $node = $doc->documentElement;

        $isHub = $method->invokeArgs($parser, [$node]);
        $this->assertFalse($isHub);
    }
}
