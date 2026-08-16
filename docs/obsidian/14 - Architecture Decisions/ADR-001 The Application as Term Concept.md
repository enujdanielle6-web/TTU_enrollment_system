# ADR-001: The Application as Term Concept

## Context
Standard student information systems usually maintain a robust `students` table. When the TTU system was designed, it merged the concepts of user identity, applicant status, and term enrollment.

## Problem
How to track a user who applies, gets rejected, applies again next year, or an enrolled student moving from 1st Year to 2nd Year?

## Decision
1. Identity is strictly stored in `users`.
2. There is no `students` table.
3. The `applications` table serves as the snapshot for a specific semester/term enrollment.
4. An `application` links the `user` to a `curriculum`, `strand`, and a specific `academic_year` and `semester`.

## Reasoning
This approach forces every enrollment period to be treated as a fresh application. It simplifies the registration process because an old student "re-applying" for their 2nd year follows the exact same workflow as a brand new applicant.

## Consequences
- **Positive:** Uniform workflow for both new and continuing students.
- **Negative:** Fragmented academic history. Querying a student's complete 4-year transcript requires joining multiple `applications` rows.
- **Negative:** Demographic data (address, parents) is redundantly stored in every `application` row instead of centrally on the `user`.

## Status
`Current / Active`
