<?php
$row = $row_view_residence ?? [];
$dependents = $dependents ?? [];
$familyReadOnly = !empty($familyReadOnly);
$disabledAttr = $familyReadOnly ? ' disabled' : '';
$familyPrefix = 'edit_';
?>
<tr>
  <td colspan="4" class="pt-3">
    <strong>SPOUSE INFORMATION</strong>
  </td>
</tr>
<tr>
  <td colspan="2">
    SPOUSE FIRST NAME
    <br>
    <input type="text" class="editInfo form-control form-control-sm residence-family-name" value="<?= barangay_h($row['spouse_first_name'] ?? '') ?>" name="edit_spouse_first_name" id="edit_spouse_first_name"<?= $disabledAttr ?>>
  </td>
  <td colspan="2">
    SPOUSE MIDDLE NAME
    <br>
    <input type="text" class="editInfo form-control form-control-sm residence-family-name" value="<?= barangay_h($row['spouse_middle_name'] ?? '') ?>" name="edit_spouse_middle_name" id="edit_spouse_middle_name"<?= $disabledAttr ?>>
  </td>
</tr>
<tr>
  <td colspan="2">
    SPOUSE LAST NAME
    <br>
    <input type="text" class="editInfo form-control form-control-sm residence-family-name" value="<?= barangay_h($row['spouse_last_name'] ?? '') ?>" name="edit_spouse_last_name" id="edit_spouse_last_name"<?= $disabledAttr ?>>
  </td>
  <td colspan="2">
    SPOUSE SUFFIX
    <br>
    <input type="text" class="editInfo form-control form-control-sm residence-family-name" value="<?= barangay_h($row['spouse_suffix'] ?? '') ?>" name="edit_spouse_suffix" id="edit_spouse_suffix"<?= $disabledAttr ?>>
  </td>
</tr>
<tr>
  <td colspan="2">
    SPOUSE BIRTH DATE
    <br>
    <input type="date" class="editInfo form-control form-control-sm" value="<?= barangay_h($row['spouse_birth_date'] ?? '') ?>" name="edit_spouse_birth_date" id="edit_spouse_birth_date"<?= $disabledAttr ?>>
  </td>
  <td colspan="2">
    SPOUSE CONTACT
    <br>
    <input type="text" maxlength="11" class="editInfo form-control form-control-sm residence-family-number" value="<?= barangay_h($row['spouse_contact'] ?? '') ?>" name="edit_spouse_contact" id="edit_spouse_contact"<?= $disabledAttr ?>>
  </td>
</tr>
<tr>
  <td colspan="2">
    SPOUSE OCCUPATION
    <br>
    <input type="text" class="editInfo form-control form-control-sm" value="<?= barangay_h($row['spouse_occupation'] ?? '') ?>" name="edit_spouse_occupation" id="edit_spouse_occupation"<?= $disabledAttr ?>>
  </td>
  <td colspan="2">
    SPOUSE EMPLOYER
    <br>
    <input type="text" class="editInfo form-control form-control-sm" value="<?= barangay_h($row['spouse_employer_name'] ?? '') ?>" name="edit_spouse_employer_name" id="edit_spouse_employer_name"<?= $disabledAttr ?>>
  </td>
</tr>
