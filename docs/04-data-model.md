# Data Model and Schema Rules

## 1. Required domain concepts

The physical schema is partially open, but the system must represent these concepts:

### Meeting

Conceptual fields:

- internal identifier;
- source meeting text;
- status;
- created/updated timestamps;
- optional safe title/label if the team chooses to support it without expanding scope.

### Analysis

Conceptual fields:

- meeting association;
- summary;
- decisions;
- action items;
- open questions;
- provider/model/config metadata when approved;
- prompt/schema version;
- started/completed timestamps;
- error category/details appropriate for persistence.

## 2. Structured result contract

At the application boundary, the analysis should be representable approximately as:

```json
{
  "summary": "string",
  "decisions": [
    { "text": "string" }
  ],
  "action_items": [
    {
      "task": "string",
      "owner": "string|null",
      "due_date": "string|null",
      "priority": "string|null"
    }
  ],
  "open_questions": [
    { "text": "string" }
  ]
}
```

This example defines concepts, not the final storage schema or exact PHP types.

## 3. TEAM DECISION — persistence strategy (`TD-006`)

The team must compare at least:

**Option A — normalized relational representation**
- analyses table;
- decisions table;
- action_items table;
- open_questions table.

**Option B — analysis record with validated JSON payload**
- meeting/analysis relational metadata;
- structured result stored as JSON.

**Option C — justified hybrid**

The decision should consider:

- project size;
- query/reporting needs;
- schema complexity;
- validation;
- ease of testing;
- future change risk without overengineering.

Record the decision in an ADR before Phase 2 persistence is finalized.

## 4. Integrity rules

- Every persisted analysis belongs to an existing meeting.
- Completion state must correspond to a valid persisted result.
- A failed analysis must retain enough safe metadata for diagnosis.
- Provider secrets are never persisted.
- Optional values use real nullability rather than invented strings such as `"N/A"` unless presentation explicitly maps null to a label.
- Dates extracted from natural language must not be silently converted into a precise calendar date when the text does not support it.

## 5. Schema authority

Before implementing queries or relationships, inspect actual migrations. No developer or coding agent may infer columns/foreign keys from this document after migrations have been approved; the real schema becomes authoritative for persisted relationships.
