# ci-baseline Specification

## Purpose

Establish a baseline CI workflow that runs quality gates on all pull requests so that broken builds are caught before merge.

## Requirements

### Requirement: CI Workflow Triggers on Pull Request

The CI workflow MUST trigger on all pull requests without branch-filter restrictions.

#### Scenario: PR opened or synchronized

- GIVEN a pull request is opened or its source branch is updated
- WHEN the pull request event fires
- THEN the CI workflow starts automatically
- AND no branch-name filter blocks the run

### Requirement: CI Runs PHPUnit Test Suite

The CI workflow MUST run the full PHPUnit test suite on PHP 8.3, SQLite (in-memory), and ubuntu-latest.

#### Scenario: Tests pass

- GIVEN the CI environment has PHP 8.3 and dependencies installed
- WHEN `composer test` executes
- THEN all Unit and Feature tests pass
- AND the test step succeeds

#### Scenario: Test failure blocks merge

- GIVEN a test fails in CI
- WHEN the workflow completes
- THEN the PR check is marked as failed
- AND the failure is visible on the pull request

### Requirement: CI Runs Pint Linter

The CI workflow MUST run the Pint linter in test mode (`pint --test`) to enforce code style.

#### Scenario: Linter passes

- GIVEN vendor directory is installed
- WHEN `./vendor/bin/pint --test` executes
- THEN all PHP files pass linting
- AND the lint step succeeds

#### Scenario: Linter failure blocks merge

- GIVEN a PHP file violates style rules
- WHEN `./vendor/bin/pint --test` executes
- THEN the lint step fails
- AND the PR check is marked as failed

### Requirement: CI Runs Composer Validation

The CI workflow MUST run `composer validate --strict` to ensure composer.json integrity.

#### Scenario: composer.json is valid

- GIVEN composer.json follows the schema
- WHEN `composer validate --strict` executes
- THEN validation succeeds
- AND no warnings or errors are produced

### Requirement: CI Runs Platform Requirements Check

The CI workflow MUST run `composer check-platform-reqs` to verify all platform dependencies are met.

#### Scenario: Platform requirements satisfied

- GIVEN PHP 8.3 is installed
- WHEN `composer check-platform-reqs` executes
- THEN all required extensions are present
- AND the check succeeds

### Requirement: CI Runs Frontend Build

The CI workflow MUST run `npm ci && npm run build` to verify frontend assets compile.

#### Scenario: Frontend builds successfully

- GIVEN `npm ci` installs all dependencies
- WHEN `npm run build` executes
- THEN Vite produces the built assets
- AND the build step succeeds

#### Scenario: Frontend build failure blocks merge

- GIVEN a Vite configuration or source error exists
- WHEN `npm run build` executes
- THEN the build step fails
- AND the PR check is marked as failed

### Requirement: CI Runs Route List Verification

The CI workflow MUST run `php artisan route:list` to verify the route table loads without boot errors.

#### Scenario: Route list loads successfully

- GIVEN the CI environment has dependencies installed
- WHEN `php artisan route:list` executes
- THEN the command exits with code 0
- AND no route table boot errors are produced

#### Scenario: Route list failure blocks merge

- GIVEN a route definition causes a boot failure
- WHEN `php artisan route:list` executes
- THEN the command exits with a non-zero code
- AND the PR check is marked as failed

### Requirement: CI Runs Larastan Static Analysis

The CI workflow MUST run Larastan (PHPStan) static analysis at level 5 on the `app/` directory to catch type-level defects before merge.

#### Scenario: Static analysis passes

- GIVEN `phpstan.neon` is configured at level 5 for `app/`
- WHEN `composer analyse` executes
- THEN PHPStan exits with code 0
- AND no static analysis errors are reported

#### Scenario: Static analysis failure blocks merge

- GIVEN a type error exists in `app/`
- WHEN `composer analyse` executes
- THEN PHPStan exits with a non-zero code
- AND the PR check is marked as failed
