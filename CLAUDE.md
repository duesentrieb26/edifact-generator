# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP library that provides fluent interfaces for generating UN/EDIFACT messages. The library produces array structures that can be encoded into valid EDI messages using the `sabas/edifact` encoder package.

## Development Commands

### Running Tests
```bash
composer test
```

Run tests with PHPUnit directly:
```bash
vendor/bin/phpunit
```

Run a specific test file:
```bash
vendor/bin/phpunit tests/GeneratorTest/InvoicTest.php
```

Run a specific test method:
```bash
vendor/bin/phpunit --filter testMethodName tests/GeneratorTest/InvoicTest.php
```

### Versioning
```bash
./bump_version.sh
```
This script increments the patch version in composer.json, commits the change, creates a git tag, and pushes the tag to origin.

### Installing Dependencies
```bash
composer install
```

## Architecture

### Class Hierarchy

```
Base
  ├── Message (abstract base for all message types)
  │     ├── Invoic (invoice messages)
  │     ├── Desadv (dispatch advice)
  │     ├── Orders (purchase orders)
  │     ├── Ordrsp (purchase order response)
  │     ├── Calinf (vessel call information)
  │     ├── Codeco (container gate-in/gate-out report)
  │     ├── Coparn (container announcement)
  │     ├── Copino (container dispatch order)
  │     ├── Coprar (container discharge/loading order)
  │     ├── Vermas (VGM transmission)
  │     └── Westim (container MNR/repair estimate)
  │
  └── Interchange (UNB/UNZ envelope wrapper)
```

### Key Design Patterns

**Fluent Interface**: All message classes use method chaining. Methods that set values return `$this` to enable chaining.

**Composition Pattern**: Messages compose segments via the `compose()` method, which builds the final array structure. The `composeByKeys()` method in Base uses an ordered array of property names to compose message content in the correct sequence.

**Traits for Reusability**: Common functionality is extracted into traits:
- `ContactPerson`: Contact information (name, email, phone, fax)
- `NameAndAddress`: NAD segment generation for addresses
- `ItemPrice`: Pricing information for line items
- `Item`: Generic item functionality
- `TransportData`: Shipping and transport details

**Item Pattern**: Each message type that includes line items has a corresponding Item class (e.g., `Invoic\Item`, `Orders\Item`, `Desadv\Item`).

### Message Flow

1. Create an `Interchange` instance with sender/receiver identifiers
2. Create a message instance (e.g., `Invoic`, `Orders`)
3. Use fluent setters to populate message data
4. Add items to the message (if applicable)
5. Call `compose()` on the message to build the segment array
6. Add the message to the interchange with `addMessage()`
7. Call `getComposed()` on the interchange to get the final array
8. Pass the array to `EDI\Encoder` to generate the EDI text

### Helper Classes

- **EdifactDate**: Converts PHP date strings to EDIFACT date formats (DATE, DATETIME, DATETIMEMINS)
- **EdiFactNumber**: Converts numeric values to EDIFACT number format (uses period as decimal separator)
- **EdifactException**: Custom exception class for validation errors

### Segment Helpers

The `Message` class provides static helpers for common EDI segments:
- `dtmSegment()`: Date/time segments
- `rffSegment()`: Reference segments
- `locSegment()`: Location segments
- `eqdSegment()`: Equipment segments (containers)
- `tdtSegment()`: Transport details
- `addFTXSegment()`: Free text segments (auto-splits text into 70-char lines)

The `Base` class provides:
- `addRFFSegment()`: Reference with auto-truncation
- `addDTMSegment()`: Date/time with format qualifiers
- `addBGMSegment()`: Beginning of message
- `addMOASegment()`: Monetary amounts
- `addPATSegment()`: Payment terms
- `addPCDSegment()`: Percentages
- `addPIASegment()`: Additional product identification (EAN)

## Working with Message Types

### Invoice (INVOIC)
See `src/Generator/Invoic.php` and `tests/GeneratorTest/InvoicTest.php` for examples. Key methods:
- Set invoice metadata: `setInvoiceNumber()`, `setInvoiceDate()`, `setDeliveryDate()`
- Set parties: `setManufacturerAddress()`, `setWholesalerAddress()`, `setDeliveryAddress()`
- Set contact: `setContactPerson()`, `setMailAddress()`, `setPhoneNumber()`
- Set financial: `setCurrency()`, `setVatNumber()`, `setPayableAmount()`
- Add items: `addItem(Item $item)`

### Container Messages (COPARN, COPINO, COPRAR, CODECO)
These messages support container-specific data:
- Container identification and ISO size/type
- Booking/BL references
- Vessel and voyage information
- Port locations (POL, POD, FND)
- VGM (verified gross mass)
- Temperature for reefers
- Dangerous goods
- Over-dimensions

CODECO and COPRAR support multiple containers per message via `addContainer()`.

### Dispatch Advice (DESADV)
Supports packages and line items. Use `Package` class to group `Item` instances. Packages can be nested for hierarchical structure.

## Testing Conventions

- Test files are in `tests/GeneratorTest/`
- Test class names match the class being tested with "Test" suffix (e.g., `InvoicTest` tests `Invoic`)
- Tests typically:
  1. Create an Interchange
  2. Build a message with test data
  3. Compose the message
  4. Add to interchange and encode
  5. Assert expected segments appear in output

## Code Style Notes

- PSR-4 autoloading: namespace `EDI\Generator` maps to `src/Generator/`
- PHP 8.2 is the target platform
- No strict typing declarations used
- Properties typically use PHPDoc type hints
- Use `self::maxChars()` to truncate strings to EDI field length limits
- Use `EdifactDate::get()` for date formatting
- Use `EdiFactNumber::convert()` for numeric value formatting
