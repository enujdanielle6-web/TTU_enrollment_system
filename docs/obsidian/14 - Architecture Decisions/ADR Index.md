# Architecture Decision Records (ADRs)

Architecture Decision Records (ADRs) document significant structural, behavioral, and architectural decisions made throughout the evolution of the TTU Enrollment System and LMS.

---

## Master ADR Index

- **[[ADR-001 The Application as Term Concept]]**: Explains why there is no central `students` table and why enrollment is anchored to term-specific application records.
- **[[ADR-002 Strangler Fig Migration]]**: Explains the strategy for incrementally migrating procedural legacy scripts to a unified Vanilla PHP Hybrid MVC architecture.
- **[[ADR-003 Hybrid SPA Navigation Design]]**: Detailed architecture and implementation specification for client-side HTML fragment swapping and history management in the LMS.
- **[[ADR-004 Hybrid Navigation Adversarial Audit]]**: Adversarial security and reliability audit of the LMS SPA navigation layer, covering race conditions, memory leaks, and DOM injection vectors.
- **[[ADR-005 Curriculum Versioning and Subject Catalog Immutability]]**: Complete specification of the 3-state curriculum lifecycle (Draft $\rightarrow$ Active $\rightarrow$ Archived), clone-to-draft versioning, and subject catalog historical integrity safeguards.
- **[[ADR-006 Deferred Account Creation via OTP]]**: Deferral of database `users` record insertion until successful recipient 6-digit OTP verification to prevent abandoned accounts and database pollution.
- **[[ADR-007 NSTP Modular Curriculum Integration]]**: RA 9163 statutory compliance, placeholder-to-track dynamic resolution (CWTS/ROTC/LTS), live preview calculation, and Admissions approval mapping.

---
**Related:**
- [[System Architecture]]
- [[System Architecture Reconnaissance]]
- [[Curriculum Architecture]]
- [[National Service Training Program (NSTP) Architecture]]
- [[Subject Catalog Immutability Architecture]]
- [[TTU Enrollment System Home]]
