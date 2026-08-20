# Architecture Decision Records (ADRs)

Architecture Decision Records (ADRs) document significant structural, behavioral, and architectural decisions made throughout the evolution of the TTU Enrollment System and LMS.

---

## Master ADR Index

- **[[ADR-001 The Application as Term Concept]]**: Explains why there is no central `students` table and why enrollment is anchored to term-specific application records.
- **[[ADR-002 Strangler Fig Migration]]**: Explains the strategy for incrementally migrating procedural legacy scripts to a unified Vanilla PHP Hybrid MVC architecture.
- **[[ADR-003 Hybrid SPA Navigation Design]]**: Detailed architecture and implementation specification for client-side HTML fragment swapping and history management in the LMS.
- **[[ADR-004 Hybrid Navigation Adversarial Audit]]**: Adversarial security and reliability audit of the LMS SPA navigation layer, covering race conditions, memory leaks, and DOM injection vectors.

---
**Related:**
- [[System Architecture]]
- [[System Architecture Reconnaissance]]
- [[TTU Enrollment System Home]]
