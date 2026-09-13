# Documentation Maintenance Standard & Synchronization Protocol

**Status**: Authoritative Institutional Standard  
**Effective Date**: September 13, 2026  
**Audience**: All Software Engineers, Systems Architects, and AI Coding Agents

---

## 1. The Core Authority Principle

> [!IMPORTANT]
> **Source code is authoritative.**
> Documentation describes the verified current implementation.
> When architecture, database schema, routes, or business behavior changes, the corresponding documentation in `docs/obsidian/` and agent skills in `.agents/` MUST be updated as part of the same pull request or development turn.

Never allow documentation to drift into an aspirational or obsolete state. Code changes are not considered complete until the documentation reflects the verified reality.

---

## 2. Synchronization Triggers & Affected Documents

Whenever an engineer or AI agent modifies a component in the codebase, the corresponding Obsidian documentation files must be updated according to this matrix:

| Codebase Modification | Mandatory Documentation Updates |
|---|---|
| **Adding or modifying database tables, columns, indexes, or constraints** (`database/schema.sql`, migrations) | 1. `04 - Database/Data Dictionary.md`<br>2. `04 - Database/Entity Relationship Architecture.md`<br>3. Relevant module docs in `02 - Modules/`<br>4. `.agents/skills/database_engineer/SKILL.md` (if structural) |
| **Adding, changing, or deleting routes or HTTP methods** (`app/Routes/web.php`) | 1. `17 - File Reference/01 - Controllers Reference.md`<br>2. `08 - API & AJAX/API Documentation.md` (if AJAX)<br>3. Relevant module doc in `02 - Modules/`<br>4. Relevant relationship map in `16 - Page Relationships/` |
| **Adding or modifying Controllers or controller methods** (`app/Controllers/`) | 1. `17 - File Reference/01 - Controllers Reference.md`<br>2. Relevant module doc in `02 - Modules/`<br>3. `16 - Page Relationships/00 - Master Relationship Index & Matrix.md` |
| **Modifying Domain Services or Repositories** (`app/Services/`, `app/Repositories/`) | 1. `17 - File Reference/03 - Services Reference.md`<br>2. Relevant Architectural Decision Record in `14 - Architecture Decisions/` |
| **Altering business rules or state transitions** (e.g. application statuses, payment gates, clinic clearance) | 1. `06 - Business Rules/Business Rules.md`<br>2. `03 - Workflows/Student Lifecycle Workflow.md`<br>3. Relevant workflow in `03 - Workflows/` |
| **Changing Models or database helpers** (`app/Models/`) | 1. `17 - File Reference/02 - Models Reference.md` |
| **Adding or modifying Views or templates** (`app/Views/`) | 1. `17 - File Reference/05 - Views Catalog & Template Mapping.md`<br>2. Relevant module doc in `02 - Modules/` |
| **Modifying core security, authentication, or middleware** (`app/Core/`, `app/Middleware/`, `.htaccess`) | 1. `07 - Security/Security Overview.md`<br>2. `17 - File Reference/04 - Core & Middleware Reference.md`<br>3. `.agents/skills/security_engineer/SKILL.md` |

---

## 3. Protocol for AI Coding Agents

When working inside this repository, AI agents must follow these operational rules:

1. **Read Before Editing**: Consult `docs/obsidian/` first to understand module boundaries, database structures, and relationships before proposing modifications.
2. **Never Blindly Trust Stale Documentation**: If an existing document contradicts the source code, verify against the active source files. The source code is always the source of truth.
3. **Repair in Place**: When finding inaccuracies in existing documentation, fix them immediately. Do NOT create duplicate competing documents.
4. **Maintain AI-Operability**: When documenting new files or methods, always provide explicit relationships:
   ```text
   ENTRY POINT / ROUTE -> CONTROLLER -> SERVICE -> REPOSITORY / MODEL -> DATABASE TABLES -> VIEW
   ```
5. **Update `.agents` Skills on Architecture Changes**: If an architectural pattern or boundary is altered (e.g., adding a new domain service or changing an auth lifecycle), update the corresponding `.agents/skills/` file so future agents do not regress the change.

---

## 4. Verification Checklist Before Marking Work Complete

Before concluding any architectural or feature work:
- [ ] Has `docs/obsidian/04 - Database/Data Dictionary.md` been verified against `database/schema.sql`?
- [ ] Have all newly added or modified methods been documented in `17 - File Reference/`?
- [ ] Are all route paths in documentation verified against `app/Routes/web.php`?
- [ ] Do all workflow sequence diagrams in `03 - Workflows/` reflect the actual controller execution path?
- [ ] Have all `.agents/skills/` been checked for consistency with new patterns?
