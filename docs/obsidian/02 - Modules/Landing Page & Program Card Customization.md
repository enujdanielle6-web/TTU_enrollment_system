# Landing Page & Program Card Customization

This document details the architectural integration between the **Registrar Program & Strand Management Module** and the **Public University Landing Page**, enabling real-time visual card editing and dynamic program offerings.

---

## 1. Overview & Architecture

To empower academic administrators to highlight programs without requiring manual code changes:
1. **Registrar Dashboard Controls:** Administrators configure program/strand visual cards directly from the College Programs (`/admin/registrar/college_programs.php`) and Senior High Strands (`/admin/registrar/shs_strands.php`) interfaces.
2. **Live Visual Preview Modal:** An interactive modal renders the card in real-time as the admin modifies badges, icons, gradients, descriptions, and feature bullets.
3. **Dynamic Public Landing Page:** The landing page controller (`HomeController@index`) queries active programs and strands from the database and renders them in the Academic Programs carousel on `/` (`app/Views/home.php`).

```mermaid
flowchart LR
    RegAdmin[Registrar Admin] -->|Customizes Badges, Icons, Text| RegView[college_programs.php / shs_strands.php]
    RegView -->|AJAX POST /admin/registrar/college/save_card| RegCtrl[CollegeController / ShsController]
    RegCtrl -->|Updates Card Config Columns| DB[(college_programs / shs_strands)]
    DB -->|Queries Published Offerings| HomeCtrl[HomeController@index]
    HomeCtrl -->|Renders Rich Cards| HomeView[app/Views/home.php]
    HomeView -->|Browsed by| Public[Prospective Students & Parents]
```

---

## 2. Database Schema Columns

The following columns on `college_programs` and `shs_strands` store card appearance settings:

| Column Name | Type | Description | Example |
|---|---|---|---|
| `card_description` | `TEXT` | Marketing synopsis displayed on landing page | `"Prepare for careers in software engineering, cloud, and AI."` |
| `card_icon` | `VARCHAR(100)` | Bootstrap Icon CSS class | `"bi-laptop"`, `"bi-cpu"`, `"bi-shield-check"` |
| `card_badge` | `VARCHAR(100)` | Featured badge chip text | `"High Demand"`, `"STEM Track"`, `"Industry Aligned"` |
| `card_color` | `VARCHAR(50)` | Theme accent or gradient key | `"primary"`, `"emerald"`, `"indigo"`, `"amber"` |
| `card_features` | `TEXT` | JSON or comma-separated list of key career highlights | `["Software Engineer", "Cloud Architect", "Data Scientist"]` |
| `is_published` | `TINYINT(1)` | Public visibility toggle (1 = Shown on landing page, 0 = Hidden) | `1` |

---

## 3. Registrar Card Customization Workflow

1. Navigate to **Registrar $\rightarrow$ College Programs** or **SHS Strands**.
2. Click the **"Edit Landing Card"** button (`btn-edit-card`) on any active program.
3. The **Interactive Card Customizer Modal** opens:
   - **Real-Time Live Card Preview:** Automatically updates styling, badge text, icon, and colors on every keystroke.
   - **Icon Picker:** Select from categorized modern academic icons.
   - **Feature Bullet Editor:** Add and remove bulleted highlight points.
   - **Visibility Switch:** Instantly publish or unpublish the card from the public landing page.
4. Clicking **Save Card Configuration** dispatches an AJAX request to the controller (`CollegeController@saveCard` / `ShsController@saveCard`), writes changes to the database, and provides immediate toast feedback.

---

## 4. Public Landing Page Integration (`home.php`)

In `HomeController::index()`:
- Fetches all college programs where `is_published = 1` and `status = 'active'`.
- Fetches all SHS strands where `is_published = 1` and `status = 'active'`.
- Fetches active scholarship programs where `is_active = 1` from `scholarships`.
- Passes `$programs`, `$strands`, and `$scholarships` collections to `app/Views/home.php`.

In `home.php`:
- Renders responsive program cards complete with dynamic icons, badges, descriptions, duration (4 Years / 2 Years), and an instant **"Enroll Now"** button leading directly to `/auth/register.php`.

---

## 5. Public Scholarship Showcase Integration (`#scholarships`)

To promote institutional accessibility and financial aid opportunities to prospective applicants:
1. **Dynamic Database Integration:** `HomeController@index` queries active scholarship grants from `scholarships`.
2. **Landing Page Showcase Section:** Rendered under `#scholarships` in `home.php` with:
   - Modern glassmorphism cards with theme color gradients (emerald, blue, amber, violet).
   - Dynamic coverage badges highlighting **100% Tuition**, **50% Tuition**, or **Monthly Living Allowance**.
   - Minimum GWA and eligibility criteria.
   - Interactive requirement preview modals and direct application links pointing to `/auth/register.php`.
3. **Navbar Anchor Navigation:** Integrated into [`navbar.php`](file:///c:/xampp/htdocs/sia/app/Views/components/navbar.php) with smooth scrolling to `#scholarships`.

---
**Related:**
- [[Applicant Registration Workflow]]
- [[Curriculum Architecture]]
- [[Scholarship]]
- [[Page Relationships Matrix]]
