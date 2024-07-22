<?php
/**
 * Property.php
 *
 * This file contains the Property class which represents a property in the lshw XML output.
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

class Property
{
    private array $values;

    /**
     * PropertyWrapper constructor.
     *
     * @param mixed $values The values to wrap. Can be a single value or an array of values.
     */
    public function __construct(mixed $values)
    {
        $this->values = is_array($values) ? $values : [$values];
    }

    /**
     * Gets the values as an array.
     *
     * @return array The values as an array.
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Gets the first value.
     *
     * @return mixed The first value.
     */
    public function getFirstValue(): mixed
    {
        return $this->values[0];
    }

    /**
     * Gets the value by index.
     *
     * @param int $index The index of the value to retrieve.
     * @return mixed|null The value at the specified index, or null if the index is out of range.
     */
    public function getValue(int $index): mixed
    {
        return $this->values[$index] ?? null;
    }

    /**
     * Gets the number of values.
     *
     * @return int The number of values.
     */
    public function getCount(): int
    {
        return count($this->values);
    }
}
