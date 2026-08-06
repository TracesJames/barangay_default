<?php

/**
 * Render a PRF single-choice radio group.
 *
 * @param array<int, string> $options
 * @param 'compact'|'wrap'|'stacked' $layout
 */
function nutrition_prf_render_radio_group(string $name, array $options, string $inputClass = '', string $layout = 'compact'): void
{
    $layoutClass = nutrition_prf_options_layout_class($layout);
    echo '<div class="' . barangay_h($layoutClass) . '">';
    foreach ($options as $i => $label) {
        $id = $name . '_' . $i;
        $classAttr = trim('custom-control-input ' . $inputClass);
        echo '<div class="custom-control custom-radio">';
        echo '<input type="radio" class="' . barangay_h($classAttr) . '" id="' . barangay_h($id) . '" name="' . barangay_h($name) . '" value="' . barangay_h($label) . '">';
        echo '<label class="custom-control-label" for="' . barangay_h($id) . '">' . barangay_h($label) . '</label>';
        echo '</div>';
    }
    echo '</div>';
}

/**
 * Render a PRF multi-choice checkbox group.
 *
 * @param array<int, string> $options
 * @param 'compact'|'wrap'|'stacked' $layout
 */
function nutrition_prf_render_checkbox_group(string $name, array $options, string $layout = 'wrap'): void
{
    $layoutClass = nutrition_prf_options_layout_class($layout);
    echo '<div class="' . barangay_h($layoutClass) . '">';
    foreach ($options as $i => $label) {
        $id = $name . '_' . $i;
        echo '<div class="custom-control custom-checkbox">';
        echo '<input type="checkbox" class="custom-control-input" id="' . barangay_h($id) . '" name="' . barangay_h($name) . '[]" value="' . barangay_h($label) . '">';
        echo '<label class="custom-control-label" for="' . barangay_h($id) . '">' . barangay_h($label) . '</label>';
        echo '</div>';
    }
    echo '</div>';
}

/**
 * @param 'compact'|'wrap'|'stacked' $layout
 */
function nutrition_prf_options_layout_class(string $layout): string
{
    return match ($layout) {
        'stacked' => 'nutrition-prf-options nutrition-prf-options--stacked',
        'wrap' => 'nutrition-prf-options nutrition-prf-options--wrap',
        default => 'nutrition-prf-options nutrition-prf-options--compact',
    };
}

function nutrition_prf_field_label(string $itemNumber, string $text): void
{
    echo '<label class="nutrition-prf-field-label">';
    echo '<span class="nutrition-prf-item-number">' . barangay_h($itemNumber) . '</span>';
    echo barangay_h($text);
    echo '</label>';
}
