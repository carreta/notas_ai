# test-baseline Specification

## Purpose

Provide an automated smoke test that verifies all default Laravel migrations succeed from a fresh in-memory SQLite database, proving the application boots and the schema is valid from zero state.

## Requirements

### Requirement: Migration Smoke Test Exists in Feature Directory

A migration smoke test file MUST exist at `tests/Feature/MigrationSmokeTest.php`.

#### Scenario: Test file is present

- GIVEN the repository is checked out
- WHEN looking in `tests/Feature/`
- THEN `MigrationSmokeTest.php` is found
- AND it is a valid PHPUnit test class

### Requirement: Test Uses RefreshDatabase Trait

The migration smoke test MUST use the `RefreshDatabase` trait to run migrations from a clean in-memory SQLite database.

#### Scenario: Database is fresh for each test

- GIVEN the test uses `RefreshDatabase`
- WHEN the test method begins
- THEN the database is migrated from scratch
- AND no prior data persists into the test

### Requirement: Test Verifies All Migrations Complete

The migration smoke test MUST successfully execute all default migrations without errors.

#### Scenario: All migrations run successfully

- GIVEN a fresh in-memory SQLite database
- WHEN the `RefreshDatabase` trait triggers `artisan migrate`
- THEN the `users`, `cache`, `password_reset_tokens`, `sessions`, and `jobs` tables are created
- AND no migration errors occur

### Requirement: Test Verifies Application Boots

The migration smoke test MUST verify the application responds with HTTP 200 on the root route.

#### Scenario: Root route returns 200

- GIVEN the application has booted with a migrated database
- WHEN a GET request is made to `/`
- THEN the response status is 200
- AND the response body is a valid Laravel welcome page

### Requirement: Test Uses PHPUnit Syntax (Not Pest)

The migration smoke test MUST use PHPUnit assertion syntax and class structure, not Pest.

#### Scenario: PHPUnit-compatible test class

- GIVEN PHPUnit 12.x is the test runner
- WHEN inspecting `MigrationSmokeTest.php`
- THEN the class extends `Tests\TestCase`
- AND uses `$this->get()` / `$response->assertStatus()` style assertions
- AND no `it()` / `test()` Pest helper functions are used
