# LSHW XML Parser

This PHP library parses XML output from the lshw command to extract hardware information on Linux systems.

## Features

- Parse lshw XML output.
- Retrieve system memory, CPU, storage device, and network interface information.
- Dynamic filtering of nodes based on properties.
- Customizable filtering logic with callable functions.
- Skiphubs can be enabled to skip hub/bridge objects.
- \>85% code coverage.

## Installation

Install the library using Composer:

`sh composer require scarlett/lshw-parser`

## Usage

Parsing the XML Output

```php
use Scarlett\LshwParser\Parser;

$xmlContent = file_get_contents('path/to/lshw-output.xml');
$parser = new Parser($xmlContent);

// Get system memory information
$memory = $parser->getSystemMemory();
echo $memory->getProperty('description')->getFirstValue();

// Get CPU information
$cpuInfo = $parser->getCpuInfo();
echo $cpuInfo->getProperty('vendor')->getFirstValue();
```

Filtering Nodes by Properties

```php
use Scarlett\LshwParser\Parser;

$xmlContent = file_get_contents('path/to/lshw-output.xml');
$parser = new Parser($xmlContent);

// Filter nodes with AND logic
$results = $parser->parseByProperties(['class' => 'processor', 'vendor' => 'Intel Corp.'], 'and');
foreach ($results as $result) {
    echo $result->getProperty('description')->getFirstValue();
}

// Filter nodes with OR logic
$results = $parser->parseByProperties(['class' => 'processor', 'vendor' => 'AMD'], 'or');
foreach ($results as $result) {
    echo $result->getProperty('description')->getFirstValue();
}
```

Custom Filtering with Callables

```php
use Scarlett\LshwParser\Parser;

$xmlContent = file_get_contents('path/to/lshw-output.xml');
$parser = new Parser($xmlContent);

$results = $parser->searchNodesByFilter(function($properties) {
    return isset($properties['vendor']) && $properties['vendor'] === 'Intel Corp.';
});

foreach ($results as $result) {
    echo $result->getProperty('description')->getFirstValue();
}
```

## Skiphubs

The skipHubs feature(`default:OFF`) in the Parser class allows for the skipping of nodes identified as hubs during parsing. The following regex patterns are used to match node descriptions:

- `/usb\s*(hub|2(\.0)?\s*hub|3(\.0)?\s*hub)/i`: Matches "usb hub", "usb2 hub", "usb2.0 hub", "usb3 hub", "usb3.0 hub".
- `/(pci(e)?\s*bridge)/i`: Matches "pci bridge", "pcie bridge".
- `/(isa\s*bridge)/i`: Matches "isa bridge".
- `/hub/i`: Matches "hub".

Setting the skipHubs flag to true enables this functionality, ensuring that such nodes are excluded from the results.
To enable `public function __construct(string $xmlContent, bool $skipHubs = true)` or when instanced `setSkipHubs(bool) -> void`.

## Running Tests

The project uses PHPUnit for unit testing. To run the tests, use the following command:

`./vendor/bin/phpunit .`

## Contributing

Contributions are welcome! Please open an issue or submit a pull request for any changes.

## License

This project is licensed under the MIT License.
