# Architecture

## 1. Fixed high-level shape

```text
Browser
   |
   v
Laravel Presentation / HTTP
   |
   v
Request Validation
   |
   v
Application Use Case / Analysis Orchestrator
   |                         |
   |                         v
   |                    Persistence
   |                         |
   v                         v
AI Port / Interface       Database
   |
   v
Provider Adapter
   |
   v
External LLM API
```

## 2. Responsibilities

### Presentation / HTTP

Responsible for routes, request/response flow, views, redirects, validation feedback, and status rendering.

Must not:
- construct provider payloads directly;
- parse raw LLM responses;
- contain core analysis rules.

### Application layer

Coordinates the meeting-analysis use case:

1. receive validated meeting identity/content;
2. establish processing state;
3. call the AI abstraction;
4. validate/convert result into application-owned data;
5. persist result/failure consistently;
6. expose result to presentation.

The team decides concrete class names and whether this is implemented as a service, action, command/handler, or similar pattern.

### AI port

The application needs a provider-neutral contract. The exact PHP interface is deliberately a team design exercise (`TD-002`).

The contract should conceptually accept application-owned analysis input and return application-owned structured result or typed failure, not a raw provider HTTP response.

### Provider adapter

Contains provider-specific:
- authentication/config;
- API request format;
- model/provider options;
- raw response extraction;
- provider exception mapping.

### Persistence

Stores meeting, status, structured result, and safe metadata. Exact normalization is `TD-006`.

## 3. Dependency direction

The application workflow may depend on an AI abstraction. The provider implementation depends on that contract, not the reverse.

Provider SDK/HTTP response classes should not become domain/application return types.

## 4. Required seams for testing

Tests must be able to cover:

- successful analysis without a network call;
- provider timeout/failure;
- invalid structured response;
- persistence failure/state behavior where practical.

## 5. Intentionally open architectural areas

The following are deliberately not pre-solved:

- `TD-002` exact AI interface/result types;
- `TD-004` synchronous vs queued analysis;
- `TD-006` normalized result tables vs JSON-oriented persistence;
- `TD-007` UI implementation approach within Laravel;
- `TD-008` database engine for team development/CI.

These choices must not violate the fixed boundaries above.

## 6. Anti-patterns

Do not:

- call the external LLM directly from a Blade view/controller;
- treat raw AI JSON as trusted because the provider returned HTTP 200;
- scatter provider model names throughout the application;
- make mandatory automated tests hit the live provider;
- introduce repositories, DTO libraries, queues, event buses, or other abstractions solely to look sophisticated;
- resolve an open architecture decision by one developer silently committing it.
