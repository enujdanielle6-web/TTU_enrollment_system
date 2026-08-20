# Scholarship Module

**Path**: `admin/scholarship/`  
**Required Roles**: `scholarship`, `admin`, `superadmin`  
**Controller**: [`ScholarshipController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scholarship/ScholarshipController.php)

The Scholarship module governs financial aid programs, student grant applications, review workflows, and automated tuition fee discount applications.

---

## 1. Core Responsibilities
1. **Scholarship Program Management:** Creates grant offerings in the `scholarships` table with defined discount types (`percentage` or `fixed_amount`), criteria, and active statuses.
2. **Application Processing:** Reviews student scholarship submissions in `scholarship_applications` across `pending`, `under_review`, `approved`, and `rejected` states.
3. **Active Scholars Registry:** Maintains the roster of awarded students in `student_scholarships` / `scholarship_recipients`.
4. **Finance Integration:** When a scholarship is approved, the discount is automatically factored into the student's tuition assessment in [`FinanceController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Finance/FinanceController.php).

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
