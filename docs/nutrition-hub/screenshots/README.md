# Nutrition Hub — Screenshot capture guide

Place PNG screenshots in **this folder** using the exact filenames below.  
The user guide [`docs/NUTRITION_HUB.md`](../../NUTRITION_HUB.md) embeds them with relative paths.  
The printable **PDF form** at `admin/nutritionHubGuidePrint.php` also loads these images (missing files show as placeholders).

## How to capture

1. Start XAMPP (Apache + MySQL) and open the app in a desktop browser (1920×1080 recommended).
2. Log in with a Super Admin (or BNS Admin) account for city shots; pick a barangay with survey data for barangay shots.
3. Capture the **full relevant UI** (sidebar + content when documenting navigation). For print pages, capture the top of each major section.
4. Save as **PNG**, RGB, no watermark.
5. Use the **exact filename** in the table (case-sensitive).

Optional: keep a copy named `*_raw.png` if you crop later; only the numbered names are linked from the guide.

## Naming checklist

| Status | Filename | Page / URL (under `admin/` unless noted) | Notes |
|--------|----------|------------------------------------------|-------|
| [ ] | `01-login.png` | `../login.php` | Full login form |
| [ ] | `02-super-dashboard-entry.png` | `superDashboard.php` | Show Nutrition Dashboard / Switch to Nutrition Hub control |
| [ ] | `03-nutrition-hub-dashboard.png` | `nutritionSuperDashboard.php` | Welcome + stats + quick actions |
| [ ] | `04-nutrition-hub-sidebar.png` | same | Crop or full page focusing on **Nutrition Hub** sidebar |
| [ ] | `05-select-barangay.png` | `barangayHub.php?picker=1&system=nutrition&view=picker` | Barangay grid |
| [ ] | `06-mellpi-city-profile.png` | `nutritionMellpiCityProfile.php` | Registration form (scroll if needed; one representative view) |
| [ ] | `07-city-print-report-cover.png` | `nutritionSuperPrintReport.php` | Cover / first page |
| [ ] | `08-city-print-mellpi.png` | same | MELLPI section |
| [ ] | `09-city-print-bnp.png` | same | BNP C1 (or C1–C9 header area) |
| [ ] | `10-city-print-eopt.png` | same | e-OPT Plus section |
| [ ] | `11-pregnant-families-city.png` | `nutritionSuperPregnantFamiliesPrint.php` | City pregnant print |
| [ ] | `12-bns-accounts.png` | `staffAccounts.php` filtered to BNS role | Super Admin only |
| [ ] | `13-barangay-dashboard.png` | `nutritionDashboard.php` | After selecting a barangay |
| [ ] | `14-barangay-sidebar.png` | same | **Nutrition Profiling** sidebar |
| [ ] | `15-household-survey-list.png` | `nutritionHouseholdSurvey.php` | List / summary |
| [ ] | `16-household-survey-form.png` | same (new/edit form open) | Head + members with child/pregnant fields visible |
| [ ] | `17-new-assessment.png` | `nutritionAssess.php` | Assessment form |
| [ ] | `18-bnp-reports.png` | `nutritionBnpReport.php` | Type selector + preview |
| [ ] | `19-bnp-print.png` | `nutritionBnpPrint.php?type=all_hh` | Printed C1 layout |
| [ ] | `20-consolidated-report.png` | `nutritionBarangaySurvey.php` | Consolidated view |
| [ ] | `21-pregnant-families-brgy.png` | `nutritionBnpReport.php?type=pregnant` | Barangay pregnant report |
| [ ] | `22-nutrition-profiles.png` | `nutritionProfiles.php` | Profiles table/filters |
| [ ] | `23-settings.png` | `nutritionSettings.php` | Settings panel |

## Suggested capture order (city → barangay)

1. Login → Super Dashboard → Nutrition Hub dashboard + sidebar  
2. Select Barangay → MELLPI → City print (cover, MELLPI, BNP, e-OPT) → City pregnant → BNS accounts  
3. Open one barangay → Dashboard + sidebar → Household survey list + form → Assessment  
4. BNP UI + print → Consolidated → Pregnant → Profiles → Settings  

## After capturing

1. Confirm all 23 files exist in this directory.
2. Open `docs/NUTRITION_HUB.md` in a Markdown previewer and scroll — images should render.
3. Tick the checkboxes in the table above for your records.

## Placeholder policy

Do **not** commit fake or blank PNGs unless the team agrees. Empty missing images are fine until real screenshots are taken; the guide still documents every step.
