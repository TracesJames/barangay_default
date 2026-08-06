<?php
$residenceNavActive = $residenceNavActive ?? '';
$showResidenceImport = !empty($showResidenceImport);
?>
<li class="nav-item">
  <a href="newResidence.php" class="nav-link <?= $residenceNavActive === 'new' ? 'active' : '' ?>">
    <i class="fas fa-circle nav-icon text-red"></i>
    <p>New Residence</p>
  </a>
</li>
<?php if ($showResidenceImport): ?>
<li class="nav-item">
  <a href="importResidence.php" class="nav-link <?= $residenceNavActive === 'import' ? 'active' : '' ?>">
    <i class="fas fa-circle nav-icon text-red"></i>
    <p>Import from Excel</p>
  </a>
</li>
<?php endif; ?>
<li class="nav-item">
  <a href="allResidence.php" class="nav-link <?= $residenceNavActive === 'all' ? 'active' : '' ?>">
    <i class="fas fa-circle nav-icon text-red"></i>
    <p>All Residence</p>
  </a>
</li>
<li class="nav-item">
  <a href="familyHouseholdHead.php" class="nav-link <?= $residenceNavActive === 'household_head' ? 'active' : '' ?>">
    <i class="fas fa-circle nav-icon text-red"></i>
    <p>Family House Hold</p>
  </a>
</li>
<li class="nav-item">
  <a href="archiveResidence.php" class="nav-link <?= $residenceNavActive === 'archive' ? 'active' : '' ?>">
    <i class="fas fa-circle nav-icon text-red"></i>
    <p>Archive Residence</p>
  </a>
</li>
