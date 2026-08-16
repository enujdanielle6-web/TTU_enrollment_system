# Curriculum Architecture

The TTU System maintains separate architectures for College and Senior High School (SHS) to accommodate their differing requirements, while allowing for strict historical versioning.

## Architecture

### College Hierarchy
1. **Program** (`college_programs`): e.g., BS Information Technology.
2. **Curriculum** (`college_curricula`): A specific version of a program (e.g., "2024 Revised").
3. **Subjects** (`college_curriculum_subjects`): The required `subjects` mapped to a specific year level and semester within the curriculum.

### SHS Hierarchy
1. **Strand** (`shs_strands`): e.g., STEM, HUMSS.
2. **Curriculum** (`shs_curricula`): Versioned curriculum for the strand.
3. **Subjects** (`shs_curriculum_subjects`): Mapped to grade levels (11, 12) and semesters.

## Versioning & Coexistence
Because a student's [[Applications Table|Application]] explicitly links to a `college_curriculum_id` or `curriculum_id` (for SHS), multiple versions of a curriculum can coexist safely. 
- A 4th-year student remains attached to the 2021 curriculum.
- A 1st-year student is attached to the 2024 curriculum.
- **Rule:** Curriculum records should be treated as **immutable** once students are enrolled against them, to preserve historical integrity.

## Sections & Scheduling
The [[Scheduler Module]] creates `college_sections` and `shs_sections` linked to a specific curriculum. 
- Subjects are assigned to these sections in `college_section_subjects`.
- Students enroll by mapping their `application_id` to these section subjects.
