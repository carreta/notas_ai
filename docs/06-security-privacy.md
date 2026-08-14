# Security and Privacy

## 1. Threat surface

The MVP accepts arbitrary user text and sends it to an external AI provider. That creates two independent trust boundaries: user input and provider output.

## 2. Mandatory controls

- Validate meeting input server-side.
- Escape/safely render user and AI-generated content.
- Use Laravel CSRF protection for state-changing browser requests.
- Never commit `.env` or credentials.
- Never log API keys or Authorization headers.
- Do not expose stack traces in production configuration.
- Do not use raw user content to construct file paths, shell commands, SQL, or executable templates.
- Validate AI response structure before persistence/display as structured data.
- Dependency additions require review.

## 3. Meeting-content privacy

Meeting text may contain sensitive information. For this exercise:

- use synthetic/non-sensitive demo data;
- developers should not paste real confidential company/customer meetings into the test instance;
- raw transcript logging is disabled by default;
- provider data-handling implications must be discussed during provider selection.

## 4. Secrets

Repository expectations:

```text
.env                ignored
.env.example        committed, no secrets
AI_PROVIDER_KEY=    placeholder only
```

If a key is accidentally committed, deleting it in a later commit is insufficient. The credential must be revoked/rotated and the incident recorded.

## 5. Prompt injection scope

The meeting text is content to analyze, not instructions controlling the application. Prompt design should clearly delimit user meeting content and the required schema.

The application must not grant the LLM tools, shell access, database write authority, or autonomous external actions in v0.1.

## 6. Release security checklist

- [ ] `.env` ignored.
- [ ] no secrets found in Git diff/history reviewed for the exercise.
- [ ] all state-changing forms use CSRF protections.
- [ ] input length enforced server-side.
- [ ] output rendered safely.
- [ ] provider errors sanitized.
- [ ] raw transcripts absent from default logs.
- [ ] live tests use non-sensitive content.
- [ ] dependency review completed.
