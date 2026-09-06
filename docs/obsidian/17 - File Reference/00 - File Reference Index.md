# File Reference Standard & Catalog Index

This directory provides exhaustive, verified, file-by-file technical documentation for all source code files in the TTU Enrollment System and LMS repository.

---

## Documentation Standard Format

Every documented file adheres to the following standardized technical profile:

```markdown
### `FileName.php`
- **File:** `FileName.php`
- **Path:** `app/Path/To/FileName.php`
- **Module:** Target Subsystem (e.g. Admissions, Registrar, Finance)
- **Feature:** Target Functional Feature
- **Purpose:** Concise 1-2 sentence description of why this file exists
- **Responsibilities:**
  - Bulleted list of specific computational or business tasks performed
- **Key Methods / Functions:**
  - `methodName(Type $param): ReturnType` — Description of action
- **Dependencies & Imports:** Classes, namespaces, or traits used
- **Database Interaction:** Tables read, inserted, updated, or deleted
- **Authorized Roles:** User roles permitted to invoke this file
- **Used By:** Routes (`web.php`), caller controllers, or parent views
- **Related Files:** Partner views, models, services, or JavaScript assets
- **Related Documentation:** Links to Obsidian architecture records or ADRs
```

---

## File Reference Catalog

The technical reference is organized into focused catalogs:

1. **[[01 - Controllers Reference]]**: Exhaustive reference for all **38 Controller classes** spanning root, administrative, API, and LMS domains.
2. **[[02 - Models Reference]]**: Technical specifications for all **10 Model classes** (`User`, `Application`, `ApplicationDocument`, `HealthRecord`, `StudentAssessment`, `ScholarshipApplication`, `Schedule`, `ActivityLog`, `Announcement`, `BaseModel`).
3. **[[03 - Services Reference]]**: Technical specifications for all **9 Domain Services** (`StudentNumberService`, `AssessmentService`, `EnrollmentService`, and 6 LMS services).
4. **[[04 - Core & Middleware Reference]]**: Technical specifications for the **6 Core framework classes** (`Router`, `Request`, `Response`, `Database`, `BaseController`, `HttpException`) and **6 Middleware interceptors**.
5. **[[05 - Views Catalog & Template Mapping]]**: Complete mapping of all **104 PHP view templates** to their handling controllers, layout parents, and data payloads.

---
**Related:**
- [[Project Structure & Code Map]]
- [[System Architecture]]
- [[Coding Standards]]
- [[DOCUMENTATION_COVERAGE_AUDIT]]
