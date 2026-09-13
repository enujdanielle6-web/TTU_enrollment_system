# Scheduler Module

**Path**: `admin/scheduler/`  
**Required Roles**: `scheduler`, `admin`, `superadmin`  
**Controller**: [`SchedulerController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scheduler/SchedulerController.php)

The Scheduler module translates curriculum subjects into timetabled, scheduled class sections with assigned faculty instructors and physical/virtual classrooms.

---

## 1. Core Responsibilities
1. **Section Creation:** Creates structured class blocks in `college_sections` and `shs_sections` (e.g. `BSIT 1-A`, `STEM 11-1`).
2. **Subject Scheduling & Matrix:** Attaches curriculum subjects to sections via `college_section_subjects` and `shs_section_subjects` with designated days (`Monday`..`Saturday`), start/end times, rooms, instructors, and delivery modes.
3. **Delivery Mode Governance:** Supports `Face to Face`, `Online Synchronous`, `Blended`, and `Asynchronous` instruction designations stored directly in `delivery_mode` on section subject rows.
4. **Faculty & Room Conflict Detection:** Prevents double-booking by verifying room and instructor availability across all active section timetables and weekly availability windows.
5. **Faculty Teaching Profiles & Workload:** Governs instructor assignments, maximum teaching unit caps (`max_teaching_units`), and subject competency tiers via `faculty_profiles` and `faculty_specializations`.
6. **Capacity Controls:** Sets maximum enrollment limits per section to prevent classroom overcrowding.

---

## 2. Core Endpoints & Actions
| Endpoint | Method | Action | Description |
|---|---|---|---|
| `/admin/scheduler/scheduler_dashboard.php` | GET | `dashboard` | Timetable metrics, section counts, room utilization stats. |
| `/admin/scheduler/college_sections.php` | GET/POST | `collegeSections` | Manages college section list, advisers, and year levels. |
| `/admin/scheduler/shs_sections.php` | GET/POST | `shsSections` | Manages senior high school section list and strand links. |
| `/admin/scheduler/schedule_builder.php` | GET | `builder` | Visual drag-and-drop timetable builder for mapping timeslots and rooms. |
| `/admin/scheduler/schedule_builder_process.php` | POST | `process` | Validates room/faculty conflicts and saves section subject schedule blocks. |

---

## 3. Visual Schedule Builder Architecture

The Schedule Builder (`schedule_builder.php`) provides an interactive calendar grid:
- **Auto-Synchronization:** When a section is loaded in the builder, subjects from the linked curriculum (`college_curriculum_subjects` or `shs_curriculum_subjects`) are synchronized into section subject records if missing.
- **Smart Day Normalization:** `normalizeDay()` maps full day names and legacy day notations (`Mon`, `Tue`, `MWF`, `TTH`) safely to the 6-day calendar grid (`col_Monday`..`col_Saturday`).
- **Unscheduled Fallback:** Any subjects with `TBA` or unplaced slots are listed in the Unscheduled sidebar for drag-and-drop or auto-allocation (`autoGenerate()`).
- **SPA Scope Isolation:** JavaScript variables and functions are encapsulated inside an Immediately Invoked Function Expression (IIFE) and globally exported to `window` to prevent variable collision syntax errors during SPA AJAX transitions. Full navigation links (`data-spa="false"`) are used for heavy canvas operations.

---

## 4. Faculty Workload & Timetable Governance

The scheduling engine interacts with three core faculty tables:
1. **`faculty_profiles`**: Tracks instructor employment status (`full_time`, `part_time`, `adjunct`), academic rank, and unit thresholds (`max_teaching_units`, typically 18.00–24.00 units).
2. **`faculty_availability`**: Enforces allowable day/time blocks per instructor (`is_available = 1`). Schedules placed outside an instructor's availability window trigger conflict warnings.
3. **`faculty_specializations`**: Maps instructor qualification levels (`Primary`, `Secondary`, `Qualified`) to specific subjects from the catalog, preventing unaccredited course assignments.

### Conflict Detection Matrix
The controller executes three validations before persisting a timetable block:
$$\text{Overlap Condition} = (\text{Slot Start} < \text{Existing End}) \land (\text{Slot End} > \text{Existing Start}) \land (\text{Day} = \text{Existing Day})$$
- **Room Conflict:** Flags any overlapping section subjects assigned to the same `room`.
- **Faculty Conflict:** Flags overlapping timetable slots assigned to the same `faculty_user_id`.
- **Capacity Fit:** Alerts if section enrollment exceeds physical classroom capacity.

---
**Related:**
- [[Registrar]]
- [[Curriculum Architecture]]
- [[Subject Catalog Immutability Architecture]]
- [[Data Dictionary]]
- [[ADR-003 Hybrid SPA Navigation Design]]

