<?php
/**
 * PropertyTest.php
 *
 * This file contains the PropertyTest class which tests the functionality of the Property class.
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
use PHPUnit\Framework\TestCase;
use Scarlett\LshwParser\Property;

#[CoversClass(Property::class)]
final class PropertyTest extends TestCase
{

    public function testSingleValue(): void
    {
        $property = new Property('value1');
        $this->assertEquals(['value1'], $property->getValues());
        $this->assertEquals('value1', $property->getFirstValue());
        $this->assertEquals(1, $property->getCount());
    }

    public function testMultipleValues(): void
    {
        $property = new Property(['value1', 'value2', 'value3']);
        $this->assertEquals(['value1', 'value2', 'value3'], $property->getValues());
        $this->assertEquals('value1', $property->getFirstValue());
        $this->assertEquals('value2', $property->getValue(1));
        $this->assertEquals('value3', $property->getValue(2));
        $this->assertNull($property->getValue(3));
        $this->assertEquals(3, $property->getCount());
    }
}
