<?php

/**
 * Reusable page header for nutrition portal pages.
 *
 * Optional:
 * - $nutritionPageIcon (string)
 * - $nutritionPageHeading (string)
 * - $nutritionPageDescription (string)
 * - $nutritionPageActions (string HTML)
 * - $nutritionBreadcrumbs (array<int, array{label:string,href?:string}>)
 */
$nutritionPageIcon = $nutritionPageIcon ?? 'fa-seedling';
$nutritionPageHeading = $nutritionPageHeading ?? ($nutritionPageTitle ?? 'Nutrition Profiling');
$nutritionPageDescription = $nutritionPageDescription ?? '';
$nutritionPageActions = $nutritionPageActions ?? '';
$nutritionBreadcrumbs = $nutritionBreadcrumbs ?? [
    ['label' => 'Dashboard', 'href' => 'nutritionDashboard.php'],
    ['label' => $nutritionPageHeading],
];
?>
        <nav class="nutrition-breadcrumb" aria-label="Breadcrumb">
          <ol class="nutrition-breadcrumb-list">
            <?php foreach ($nutritionBreadcrumbs as $index => $crumb) :
                $isLast = $index === count($nutritionBreadcrumbs) - 1;
                $label = (string) ($crumb['label'] ?? '');
                $href = trim((string) ($crumb['href'] ?? ''));
                ?>
            <li class="nutrition-breadcrumb-item<?= $isLast ? ' is-active' : '' ?>">
              <?php if (!$isLast && $href !== '') : ?>
              <a href="<?= barangay_h($href) ?>"><?= barangay_h($label) ?></a>
              <?php else : ?>
              <span><?= barangay_h($label) ?></span>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ol>
        </nav>

        <div class="nutrition-page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
          <div class="nutrition-page-header-main">
            <h2 class="nutrition-page-title">
              <span class="nutrition-page-title-icon"><i class="fas <?= barangay_h($nutritionPageIcon) ?>"></i></span>
              <?= barangay_h($nutritionPageHeading) ?>
            </h2>
            <?php if ($nutritionPageDescription !== '') : ?>
            <p class="nutrition-page-description mb-0"><?= barangay_h($nutritionPageDescription) ?></p>
            <?php endif; ?>
          </div>
          <?php if ($nutritionPageActions !== '') : ?>
          <div class="nutrition-page-actions d-flex flex-wrap gap-2">
            <?= $nutritionPageActions ?>
          </div>
          <?php endif; ?>
        </div>
