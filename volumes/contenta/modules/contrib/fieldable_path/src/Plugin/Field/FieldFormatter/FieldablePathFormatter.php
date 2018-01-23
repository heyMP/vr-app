<?php

namespace Drupal\fieldable_path\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_example_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "fieldable_path_formatter",
 *   module = "fieldable_path",
 *   label = @Translation("Fieldable Path"),
 *   field_types = {
 *     "fieldable_path"
 *   }
 * )
 */
class FieldablePathFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $item->value,
      ];
    }

    return $elements;
  }

}
