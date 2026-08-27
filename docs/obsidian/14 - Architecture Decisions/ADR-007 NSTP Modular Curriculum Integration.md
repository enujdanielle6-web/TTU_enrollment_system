# ADR-007: NSTP Modular Curriculum Integration and Track Mapping

## Status
**Accepted**

## Date
2026-08-27

## Context
Philippine Republic Act No. 9163 (National Service Training Program Act of 2001) mandates that tertiary baccalaureate students complete two 3-unit semesters of NSTP under one of three tracks: CWTS, ROTC, or LTS.

Previously, the curriculum catalog lacked formal NSTP subject codes, causing:
1. Incomplete 1st-year subject loads (9 units instead of the required 12 units).
2. Lack of track distinction (CWTS vs ROTC vs LTS) on student study loads, assessments, and grade sheets.
3. No mechanism to schedule weekend field/training sessions.

## Decision
We implemented a modular **Placeholder-to-Track Mapping** architecture:
1. **Catalog Registration:** Registered base subjects `NSTP101` / `NSTP102` (3 units each) as curriculum placeholders, plus track-specific subjects `CWTS101`, `CWTS102`, `ROTC101`, `ROTC102`, `LTS101`, `LTS102`.
2. **Curriculum Binding:** All college curricula bind the base `NSTP101` (Sem 1) and `NSTP102` (Sem 2).
3. **Live Applicant Preview:** The Applicant API dynamically transforms `NSTP101` into the applicant's chosen track code/title in real-time on `/applicant/enroll.php`.
4. **Admissions Dynamic Enrollment:** On application approval, `AdmissionsController@approve` maps the general `NSTP101` subject to the applicant's chosen track (`CWTS101`, `ROTC101`, or `LTS101`) and records it in `college_enrollments`.
5. **Section Scheduling:** Saturday morning schedules (8:00 AM – 11:00 AM, Quadrangle/Gym) are configured for all 1st-year section offerings.

## Consequences

### Positive
- Strict statutory compliance with CHED and RA 9163 guidelines.
- Accurate 12-unit first-semester curriculum load.
- Seamless student experience with real-time curriculum preview and accurate transcript enrollment.

---
**Related:**
- [[National Service Training Program (NSTP) Architecture]]
- [[Curriculum Architecture]]
- [[Subject Catalog Immutability Architecture]]
- [[ADR-005 Curriculum Versioning and Subject Catalog Immutability]]
