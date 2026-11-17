<?php

include_once '../connection.php';
include_once '../includes/auth_admin.php';
require_once '../includes/partials/nutrition_init.php';

$activePage = 'school_entry';
$nutritionPageTitle = 'Manual entry — Educational institutions';
$nutritionIncludeScriptsCsrf = true;

if (!nutrition_user_can_edit_form_c1_school($con, (string) $user_id, (string) $barangay_id)) {
    header('Location: nutritionDashboard.php');
    exit;
}

$formC1Settings = nutrition_bnp_load_form_c1($con, (string) $barangay_id);
$c1SchoolReadonly = false;

require __DIR__ . '/../includes/partials/nutrition_layout_start.php';
?>
        <?php
        $nutritionPageIcon = 'fa-school';
        $nutritionPageHeading = 'Manual entry — Educational institutions';
        $nutritionPageDescription = 'Day care, elementary, kindergarten, and school weighing counts for ' . $barangay . '. These print on BNP Form C1 items 16–20.';
        require __DIR__ . '/../includes/partials/nutrition_page_header.php';
        ?>

        <?php if (isset($_GET['c1_saved'])) : ?>
        <div class="alert alert-success">School figures saved. They will print on the ALL HOUSEHOLDS form, items 16–20.</div>
        <?php endif; ?>

        <div class="card nutrition-panel">
          <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-school mr-2"></i>Items 16–20</h3>
          </div>
          <form method="post" action="saveNutritionSettings.php">
            <?= csrf_field() ?>
            <input type="hidden" name="bnp_c1_section" value="school">
            <input type="hidden" name="return_page" value="nutritionSchoolEntry.php">
            <div class="card-body">
              <p class="text-muted small">These counts are not taken from household surveys. Item 20 fills from items 18 and 19 when you leave it blank.</p>
              <?php require __DIR__ . '/../includes/partials/nutrition_bnp_c1_school_entry.php'; ?>
            </div>
            <?php if (!$c1SchoolReadonly) : ?>
            <div class="card-footer text-right">
              <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Save school figures</button>
            </div>
            <?php else : ?>
            <div class="card-footer">
              <p class="text-muted mb-0">View only. You cannot change these figures for this barangay.</p>
            </div>
            <?php endif; ?>
          </form>
        </div>
<?php
require __DIR__ . '/../includes/partials/nutrition_layout_end.php';
