# Definition of Done and Traceability

## Requirement / Issue Done

A change is Done only when all applicable items are true:

- [ ] linked requirement/Issue is identified;
- [ ] acceptance criteria are satisfied;
- [ ] implementation is complete for the agreed scope;
- [ ] happy path automated test exists;
- [ ] relevant failure/edge cases are tested;
- [ ] focused tests pass;
- [ ] required full checks pass before merge according to phase policy;
- [ ] code formatting passes;
- [ ] static analysis passes if configured;
- [ ] schema references match real migrations;
- [ ] security implications reviewed;
- [ ] AI output validation is covered where relevant;
- [ ] no secret is committed;
- [ ] documentation/ADR updated;
- [ ] PR reviewed by non-author;
- [ ] review comments resolved;
- [ ] no undocumented architecture deviation exists.

## Phase Done

Additionally:

- [ ] phase FRs trace to tests;
- [ ] phase PRs merged into integration branch;
- [ ] integration suite passes;
- [ ] app boots;
- [ ] build passes if applicable;
- [ ] clean migration verified when schema changed;
- [ ] manual acceptance completed when specified;
- [ ] phase checkpoint report recorded.

## Initial traceability matrix

| Requirement | Automated evidence target | Manual evidence target | Status |
|---|---|---|---|
| FR-001 | request validation feature tests | submit valid/blank/too-long text | Planned |
| FR-002 | persistence/state feature tests | open saved meeting | Planned |
| FR-003 | orchestration with fake provider | optional live-provider smoke | Planned |
| FR-004 | parser/schema unit tests | malformed provider scenario if tooling permits | Planned |
| FR-005 | persistence/transaction tests | completed detail | Planned |
| FR-006 | result page feature tests | visual result verification | Planned |
| FR-007 | history/detail feature tests | browser navigation | Planned |
| FR-008 | provider failure feature tests | user-facing error review | Planned |
| FR-009 | based on decision | retry/reanalysis if implemented | Pending decision |
| FR-010 | metadata persistence tests | diagnostic record inspection | Planned |
| FR-011 | if implemented | readiness display | Optional |
| FR-012 | fixture test/availability | demo run | Planned |
