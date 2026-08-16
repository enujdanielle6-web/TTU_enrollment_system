# Scheduler Module

**Path**: `admin/scheduler/`
**Role Required**: `scheduler` or `superadmin`

The Scheduler module was spun out of the Registrar module to handle the dedicated task of creating class sections and timetables.

## Core Responsibilities
1. **Section Management**: Creating class sections for Senior High School and College.
2. **Schedule Building**: Using `schedule_builder.php` to automatically or manually generate timetables for sections, ensuring there are no room/time conflicts for students and faculty.
3. **Capacity Management**: Setting and monitoring the capacity limits of each section.

## Key Files
- `scheduler_dashboard.php`: The main entry point displaying active section counts.
- `shs_sections.php` / `college_sections.php`: Interfaces for managing blocks of students.
- `schedule_builder.php`: The conflict-detection timetable generator.

## Data Flow
The Scheduler interacts directly with the `college_sections` and `shs_sections` tables in the [[Database Schema]]. When creating schedules, it queries `college_curriculum` to ensure the correct subjects are being scheduled for a given block.
