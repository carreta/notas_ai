# readme-quickstart Specification

## Purpose

Provide a clean-checkout quickstart section in the root `README.md` so that any contributor can boot the application and run tests from a fresh clone using documented commands.

## Requirements

### Requirement: README Contains Clean-Checkout Setup Section

The root `README.md` MUST contain a "Setup from clean checkout" section that documents the full local setup workflow.

#### Scenario: Setup section is present

- GIVEN the repository is freshly cloned
- WHEN reading `README.md`
- THEN a "Setup from clean checkout" section is found
- AND it documents all commands needed to get running locally

### Requirement: Setup Section Documents `composer setup`

The setup section MUST document running `composer setup` as the primary setup command.

#### Scenario: Contributor runs composer setup

- GIVEN a contributor has PHP 8.3 and Composer installed
- WHEN they run `composer setup`
- THEN dependencies are installed, `.env` is created, app key is generated, and database is migrated

### Requirement: Setup Section Documents Local Server Start

The setup section MUST document starting the local development server with `php artisan serve`.

#### Scenario: Contributor starts the dev server

- GIVEN setup is complete
- WHEN the contributor runs `php artisan serve`
- THEN the application serves on `http://localhost:8000`
- AND visiting the URL shows the Laravel welcome page

### Requirement: Setup Section Documents Running Tests

The setup section MUST document running tests with `composer test` (or `php artisan test`).

#### Scenario: Contributor runs the test suite

- GIVEN a contributor has run `composer setup`
- WHEN they run `composer test`
- THEN the full PHPUnit test suite executes
- AND all tests pass including the migration smoke test
