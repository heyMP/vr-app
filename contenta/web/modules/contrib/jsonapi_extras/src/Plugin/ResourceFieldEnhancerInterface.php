<?php

namespace Drupal\jsonapi_extras\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Provides an interface defining a ResourceFieldEnhancer entity.
 */
interface ResourceFieldEnhancerInterface extends ConfigurablePluginInterface {

  /**
   * Apply the last transformations to the output value of a single field.
   *
   * @param mixed $value
   *   The value to be processed after being prepared for output.
   *
   * @return mixed
   *   The value after being post processed.
   */
  public function postProcess($value);

  /**
   * Apply the initial transformations to the input value of a single field.
   *
   * @param mixed $value
   *   The value to be processed so it can be used as an input.
   *
   * @return mixed
   *   The value after being post precessed.
   */
  public function prepareForInput($value);

  /**
   * Get the JSON Schema for the new output.
   *
   * @return array
   *   An structured array representing the JSON Schema of the new output.
   */
  public function getJsonSchema();

  /**
   * Get a form element to render the settings.
   *
   * @param array $resource_field_info
   *   The resource field info.
   *
   * @return array
   *   The form element array.
   */
  public function getSettingsForm(array $resource_field_info);

}
