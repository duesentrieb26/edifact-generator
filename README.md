# EDIFACT Generator

A PHP library that provides fluent interfaces for generating UN/EDIFACT messages.

The resulting array structures can be encoded into valid EDI messages using the [`sabas/edifact`](https://github.com/PHPEdifact/edifact) encoder package.

## Installation

```bash
composer require duesentrieb26/edifact-generator
```

### Requirements

- PHP 8.2+
- [sabas/edifact](https://github.com/PHPEdifact/edifact) ~0.7.0

## Supported Message Types

| Type | Description |
|------|-------------|
| **INVOIC** | Invoice message |
| **ORDERS** | Purchase order |
| **ORDRSP** | Purchase order response |
| **DESADV** | Dispatch advice (shipping notification) |
| **CALINF** | Vessel call information |
| **CODECO** | Container gate-in/gate-out report |
| **COPARN** | Container announcement |
| **COPINO** | Container pre-notification / dispatch order |
| **COPRAR** | Container discharge/loading order |
| **VERMAS** | Verified gross mass (VGM) transmission |
| **WESTIM** | Container MNR/repair estimate |

## Quick Start

Messages are built using a fluent interface and then composed into an interchange envelope:

```php
<?php

use EDI\Encoder;
use EDI\Generator\Interchange;
use EDI\Generator\Invoic;
use EDI\Generator\Invoic\Item;

// 1. Create the interchange envelope
$interchange = new Interchange(
    'UNB-Identifier-Sender',
    'UNB-Identifier-Receiver'
);
$interchange->setCharset('UNOC', '3');

// 2. Build the message
$invoice = new Invoic();
$invoice
    ->setInvoiceNumber('INV12345')
    ->setInvoiceDate('20240115')
    ->setDeliveryDate('20240110')
    ->setInvoiceDescription('Monthly invoice')
    ->setManufacturerAddress('Company A', 'Dept.', '', 'Main St. 1', '10115', 'Berlin', 'DE')
    ->setWholesalerAddress('Company B', '', '', 'Market Ave. 5', '20095', 'Hamburg', 'DE')
    ->setDeliveryAddress('Company B', 'Warehouse', '', 'Dock Rd. 12', '20095', 'Hamburg', 'DE')
    ->setContactPerson('John Doe')
    ->setMailAddress('john.doe@company.com')
    ->setPhoneNumber('+49123456789')
    ->setVatNumber('DE 123456789')
    ->setCurrency('EUR');

// 3. Add line items
$item = new Item();
$item
    ->setPosition(1, 'ART-001')
    ->setQuantity(5)
    ->setNetPrice(22.50)
    ->setGrossPrice(26.78)
    ->setOrderNumberWholeSaler('PO-2024-001')
    ->setOrderDate('20240105');

$invoice->addItem($item);

// 4. Set totals
$invoice
    ->setTotalPositionsAmount(112.50)
    ->setBasisAmount(112.50)
    ->setTaxableAmount(112.50)
    ->setPayableAmount(133.88)
    ->setTax(19, 21.38);

// 5. Compose and encode
$invoice->compose();

$encoder = new Encoder(
    $interchange->addMessage($invoice)->getComposed(),
    true
);
$encoder->setUNA(":+,? '");

$ediMessage = $encoder->get();
```

## Message Flow

1. Create an `Interchange` instance with sender/receiver identifiers
2. Create a message instance (e.g. `Invoic`, `Orders`, `Desadv`)
3. Use fluent setters to populate message data
4. Add items or containers to the message (if applicable)
5. Call `compose()` on the message
6. Add the message to the interchange with `addMessage()`
7. Call `getComposed()` on the interchange to get the final array
8. Pass the array to `EDI\Encoder` to generate the EDI output

## More Examples

See [SAMPLES.md](SAMPLES.md) for detailed code examples covering VERMAS, COPINO, COPARN, CODECO, COPRAR, and WESTIM message types.

## Architecture

### Class Hierarchy

```
Base
  ├── Message (abstract base for all message types)
  │     ├── Invoic
  │     ├── Desadv
  │     ├── Orders
  │     ├── Ordrsp
  │     ├── Calinf
  │     ├── Codeco
  │     ├── Coparn
  │     ├── Copino
  │     ├── Coprar
  │     ├── Vermas
  │     └── Westim
  │
  ├── Item classes (Invoic\Item, Orders\Item, Ordrsp\Item, Desadv\Item)
  ├── Container classes (Codeco\Container, Coprar\Container, Vermas\Container)
  ├── Desadv\Package / Desadv\PackageItem
  └── Westim\Damage

Interchange (UNB/UNZ envelope wrapper)
```

### Traits

Reusable functionality is extracted into traits:

- **ContactPerson** -- contact information (name, email, phone, fax)
- **NameAndAddress** -- NAD segment generation for addresses
- **ItemPrice** -- pricing information for line items
- **Item** -- generic item functionality
- **TransportData** -- shipping and transport details
- **DeliveryTerms** -- delivery terms and conditions

### Helpers

- **EdifactDate** -- converts PHP date strings to EDIFACT date formats
- **EdiFactNumber** -- converts numeric values to EDIFACT number format
- **EdifactException** -- custom exception class for validation errors

## Development

### Running Tests

```bash
composer test
```

Run a specific test file:

```bash
vendor/bin/phpunit tests/GeneratorTest/InvoicTest.php
```

### Dev Container

The project includes a dev container configuration for VS Code. Open the project in a dev container to get a pre-configured PHP environment.

## License

This project is licensed under the [LGPL-3.0+](LICENSE) license.
