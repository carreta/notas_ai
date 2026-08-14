# AI Integration Contract

## 1. Objective

Use an LLM to transform unstructured meeting text into an application-owned structured analysis.

The AI is a dependency, not the authority for application state.

## 2. Provider selection

`TEAM DECISION TD-009`: select the provider/model approach during Phase 0/2 planning. The team must verify the provider's current official API documentation at implementation time.

Selection criteria:

- structured-output support or reliable JSON mode;
- PHP/Laravel integration effort;
- cost for a small exercise;
- latency;
- testing/mocking ergonomics;
- credential setup;
- data/privacy implications.

Do not choose a provider because a developer already has sample code without evaluating the project contract.

## 3. Application-owned AI contract

Provider requests should not be built around presentation concerns. The input should be meeting content plus explicit analysis/schema version/configuration required by the application.

Provider result must be transformed into the application structure defined by the project.

## 4. Extraction rules

The AI should:

- summarize only the supplied meeting content;
- identify explicit decisions;
- identify concrete action items;
- associate an owner only when reasonably supported by the text;
- retain null/unknown when no owner is identifiable;
- include a due date only when stated or when the agreed date-resolution policy supports it;
- identify unresolved questions/topics;
- avoid inventing facts to make sections non-empty.

## 5. Priority policy — TEAM DECISION `TD-010`

The team must choose one policy:

A. Extract priority only when explicitly stated.  
B. Allow the model to infer priority and mark it as inferred.  
C. Remove priority from v0.1 if the ambiguity is not worth the complexity.

The UI, prompt, validation, and tests must align with the chosen policy.

## 6. Date policy — TEAM DECISION `TD-011`

The team must define how relative dates such as "Friday", "tomorrow", or "next week" are represented.

Minimum acceptable options:

- preserve raw natural-language date text;
- resolve relative dates using an explicit meeting/reference date and retain provenance;
- defer precise normalization in v0.1.

Never fabricate a date without a documented rule.

## 7. Prompt/schema versioning

The project must identify the prompt/schema version used for an analysis, e.g. `meeting-analysis-v1`.

A material change to required AI output structure should update this identifier and associated tests.

## 8. Validation boundary

A provider response is valid only after:

1. successful provider call;
2. expected response content exists;
3. JSON/structured result parses;
4. required fields/types validate;
5. semantic normalization rules are applied;
6. application-owned result is created.

An HTTP 200 response alone is not success.

## 9. Failure mapping

At minimum map failures to:

- configuration missing;
- provider unavailable/network error;
- timeout;
- rate limit when identifiable;
- malformed/invalid structured output;
- unexpected internal failure.

Provider-specific exception text must not become the sole user-facing error contract.

## 10. Testing strategy

Mandatory automated tests use fake provider implementations/responses.

Required cases:

- complete valid result;
- valid empty decisions/action items/questions;
- optional owner absent;
- optional due date absent;
- malformed response;
- wrong field type;
- missing required field;
- provider timeout;
- provider generic error;
- input rejected before provider call.

A live-provider smoke test may exist as optional/manual verification but must not be required for ordinary CI.

## 11. Cost guardrails

This is a small team exercise. The team should avoid uncontrolled repeated calls. At minimum:

- validation occurs before provider call;
- tests do not use paid live calls;
- accidental loops/retries are prevented;
- retry policy is explicit;
- developers know when a real provider request is being made.
