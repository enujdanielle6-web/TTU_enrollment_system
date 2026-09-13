# Scholarship Module

**Path**: `admin/scholarship/`  
**Required Roles**: `scholarship`, `admin`, `superadmin`  
**Controller**: [`ScholarshipController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scholarship/ScholarshipController.php)

The Scholarship module governs financial aid programs, student grant applications, review workflows, and automated tuition fee discount applications.

---

## 1. Core Responsibilities
1. **Scholarship Program Management:** Creates grant offerings in the `scholarships` table with defined discount types (`percentage` or `fixed_amount`), criteria, and active statuses.
2. **Public Landing Page Showcase:** Active grants (`is_active = 1`) are queried dynamically by `HomeController@index` and rendered in the interactive `#scholarships` showcase on the university homepage ([`home.php`](file:///c:/xampp/htdocs/sia/app/Views/home.php)), displaying coverage badges (e.g. 100% Tuition, Monthly Stipend), requirements modals, and direct application routes.
3. **Application Processing:** Reviews student scholarship submissions in `scholarship_applications` across `pending`, `under_review`, `approved`, and `rejected` states.
4. **Active Scholars Registry:** Maintains the roster of awarded students in `scholarship_recipients`.
5. **Automated Assessment Discounting:** When an applicant or student has an approved scholarship in `scholarship_recipients`, `App\Services\AssessmentService::calculate()` automatically factors the discount into the assessment calculation, deducting percentage or fixed-amount discounts from assessed tuition fees and freezing the net amount into `assessment_items`.
6. **Automated Verification:** End-to-end lifecycle verified by `scripts/test_irregular_scholarship_bot.php` (verifying 100% tuition deduction, net fee cashiering, and matriculation).

---

## 2. Core Endpoints & Actions
| Endpoint | Method | Action | Description |
|---|---|---|---|
| `/admin/scholarship/scholarship_dashboard.php` | GET | `dashboard` | Summary statistics of active grants, applications, and budget impact. |
| `/admin/scholarship/scholarships.php` | GET | `index` | List of configured scholarship programs with create/edit forms. |
| `/admin/scholarship/scholarship_review.php` | GET | `review` | Filterable table of incoming student scholarship applications. |
| `/admin/scholarship/scholarship_detail.php` | GET | `detail` | Individual applicant review screen with academic grades and income docs. |
| `/admin/scholarship/scholars.php` | GET | `scholars` | Masterlist of approved scholars and discount awards. |
| `/admin/scholarship/scholarship_process.php` | POST | `process` | Approves or rejects applications and updates recipient records. |

---
**Related:**
- [[Finance]]
- [[Payment & Assessment Workflow]]
- [[Applicant Portal]]
- [[Landing Page & Program Card Customization]]
- [[ADR-011 Multi-Section LMS Subject Instance Isolation and Irregular Student Subject Preservation]]
