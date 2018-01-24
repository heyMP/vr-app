<?php

namespace Drupal\fieldable_path\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of field widget for fieldable_path field.
 *
 * @FieldWidget(
 *   id = "fieldable_path_widget",
 *   module = "fieldable_path",
 *   label = @Translation("Fieldable Path"),
 *   field_types = {
 *     "fieldable_path"
 *   }
 * )
 */
class FieldablePathWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['value'] = $element + [
      '#type' => 'textfield',
      '#description' => $this->t('This is auto-generated field. Its value will always match "path" property.'),
      '#value' => !empty($items[$delta]->value) ? $items[$delta]->value : '',
      // Never let anyone change the field value manually.
      '#disabled' => TRUE,
    ];

    return $element;
  }

}
