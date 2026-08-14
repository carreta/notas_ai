# Testing and Quality Gates

## 1. Testing philosophy

Use the smallest test level that proves the behavior. External AI variability must not make the required test suite nondeterministic.

## 2. Test categories

### Unit

Use for:
- AI response parser/validator;
- application-owned result/value types;
- date/priority normalization rules if implemented;
- error mapping where pure.

### Feature/integration

Use for:
- submission validation;
- database persistence;
- meeting detail/history;
- analysis orchestration with fake provider;
- state transitions;
- controlled failures.

### Manual acceptance

Use for:
- browser workflow;
- loading/error/result rendering;
- one optional real-provider analysis;
- responsive usability.

## 3. Mandatory critical scenarios

1. Valid meeting -> completed structured result.
2. Blank meeting -> validation error, no provider call.
3. Over-limit meeting -> validation error, no provider call.
4. Provider timeout -> failed/controlled state.
5. Provider returns malformed structure -> controlled invalid-response failure.
6. Completed meeting appears in history and detail.
7. Failed meeting is visibly failed, never falsely completed.
8. Optional fields render correctly when null/absent.
9. Script-like input/output is displayed safely.

## 4. Baseline Laravel gates

Use only tools actually installed/configured. Expected baseline:

```bash
php artisan test
./vendor/bin/pint --test
composer validate --strict
composer check-platform-reqs
npm run build
php artisan route:list
```

Static analysis (PHPStan/Larastan) is `TEAM DECISION TD-012`. If selected, it becomes mandatory once configured.

## 5. CI minimum

At minimum the GitHub workflow should execute enough checks to prevent obviously broken merges. The team decides exact jobs, caching, supported runtime matrix, and whether build/static analysis run in CI.

Mandatory principle: a required check that fails blocks merge.

## 6. Clean database verification

Before release candidate:

- create/reset an empty test/development database;
- run migrations from zero;
- run relevant seed/fixture setup if documented;
- execute smoke workflow.

## 7. Evidence format

PRs/checkpoints should report exact evidence, e.g.:

```text
Focused tests: 8 tests — PASS
Full suite: 31 tests / 104 assertions — PASS
Pint: PASS
Build: PASS
Clean migrations: PASS
Architecture deviations: none
```

Do not write only "all tests pass" when exact command/results are available.
