# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP library that provides fluent interfaces for generating UN/EDIFACT messages. The library produces array structures that can be encoded into valid EDI messages using the `sabas/edifact` encoder package (`EDI\Encoder`). Usage examples: `README.md` (INVOIC) and `SAMPLES.md` (VERMAS, COPINO, COPARN, CODECO, COPRAR, WESTIM).

## Development Commands

```bash
composer install                                                        # install dependencies
composer test                                                           # run all tests (phpunit)
vendor/bin/phpunit tests/GeneratorTest/InvoicTest.php                   # single file
vendor/bin/phpunit --filter testMethodName tests/GeneratorTest/InvoicTest.php  # single method
```

CI (`.github/workflows/test.yml`) runs PHPUnit 12 on PHP 8.3 on every push. A dev container (`.devcontainer/`) and `compose.yaml` provide a PHP 8.3 environment; `docker compose run --rm php` installs dependencies and runs the tests.

### Versioning
`./bump_version.sh` increments the patch version in `composer.json`, commits, creates a git tag and runs `git push origin --tags` (tags only, the branch is not pushed). Releases are a separate "Bump version to x.y.z" commit after the feature commit. The script uses GNU `grep -P` and `sed -i`, so it fails with the stock macOS tools.

## Architecture

### Class Hierarchy

```
Base
  ├── Message (base for all message types; adds UNH/UNT in compose())
  │     ├── Invoic, Orders, Ordrsp, Desadv         (commerce, ITEK35 profile)
  │     └── Calinf, Codeco, Coparn, Copino, Coprar, Vermas, Westim  (container/shipping)
  └── Invoic\Item, Orders\Item, Ordrsp\Item, Desadv\Item, Desadv\Package, Desadv\PackageItem

Standalone (no Base): Interchange (UNB/UNZ envelope), Codeco\Container, Coprar\Container, Vermas\Container, Westim\Damage
```

### How composition works

Two styles exist; follow whichever the class you are editing uses.

**composeKeys style** (Invoic, Orders, Ordrsp, Desadv, all Item classes via `traits/Item.php`):
- Setters build a finished segment array (e.g. `['DTM', [...]]`) or a list of segments and store it in a property.
- `$composeKeys` lists property names; `Base::composeByKeys()` appends them to `messageContent` **in that order**, so the array order is the output segment order. `null` properties are skipped; a falsy non-null value throws `EdifactException`.
- To add a segment: add the property, a setter that stores the segment, and the property name at the right position in `$composeKeys`.
- `Invoic` has a second list, `$composeKeysAfterPositions`, emitted after the line items (UNS separator, totals, tax).
- Segment order matters to downstream ITEK35/EDITEC parsers; much of the git history is order fixes. Don't reorder keys casually and update test expectations when you do.

**Hand-built style** (container messages such as Coparn, Codeco): `compose()` assigns `messageContent` segment by segment.

`Message::compose()` then wraps `messageContent` with UNH/UNT and throws if it is empty.

### Defaults and conventions
- Commerce messages default to `D`/`96B`/`UN` with association `ITEK35` (see constructors).
- `Message::addFTXSegment()` splits text on word boundaries via `splitTextOnWordBoundary()` (70 chars per line, max 5 lines, UTF-8 safe). Its C107 is omitted or emitted as `[ref, '89']`, never with an empty middle component, because ITEK35-strict parsers reject `HAE::89`.
- `Base::maxChars()` truncates to field limits (default 35).
- `EdifactDate::get()` formats dates (`DATE`, `DATETIME`, `DATETIMEMINS`).
- `EdiFactNumber::convert()` formats numbers with a **comma** decimal separator by default (`DECIMAL_POINT` is optional); tests encode with UNA `:+,? '`.
- Static segment builders live in `Message` (`dtmSegment`, `rffSegment`, `locSegment`, `eqdSegment`, `tdtSegment`, `addFTXSegment`) and `Base` (`addRFFSegment`, `addDTMSegment`, `addBGMSegment`, `addMOASegment`, `addPATSegment`, `addPCDSegment`, `addPIASegment`).

### Traits
Traits are in `src/Generator/traits/` (lowercase directory) under namespace `EDI\Generator\Traits`: `ContactPerson`, `NameAndAddress`, `ItemPrice`, `Item`, `TransportData`, `DeliveryTerms`. PSR-4 would look for `Traits/`, so they load only through the `classmap` entry in `composer.json`. Run `composer dump-autoload` after adding a file there.

## Testing

- Tests live in `tests/GeneratorTest/` (namespace `GeneratorTest`), bootstrapped by `tests/Bootstrap.php`.
- Coverage exists only for Invoic, Orders, Ordrsp, Desadv, Interchange, Base, the NameAndAddress and ContactPerson traits (tested via anonymous classes extending `Base`), the date/number helpers and text splitting. The container messages have no tests.
- Typical test: build an `Interchange` and a message, `compose()`, encode with `EDI\Encoder`, then assert on the encoded EDI string or segment arrays.

## Code Style

- Library code targets PHP 8.2+; the dev toolchain (PHPUnit 12) needs PHP 8.3. No `declare(strict_types=1)`, PHPDoc type hints on properties.
- Indentation is mixed: 2 spaces in `Base`, `Message`, `Invoic` and others, 4 spaces in `Orders`, `Desadv` and the container messages. Match the file you edit.
- Setters return `$this` for chaining.
