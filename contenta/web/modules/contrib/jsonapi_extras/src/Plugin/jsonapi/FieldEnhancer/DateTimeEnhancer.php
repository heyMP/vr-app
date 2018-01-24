<?php

namespace Drupal\jsonapi_extras\Plugin\jsonapi\FieldEnhancer;

use Drupal\jsonapi_extras\Plugin\DateTimeEnhancerBase;

/**
 * Perform additional manipulations to timestamp fields.
 *
 * @ResourceFieldEnhancer(
 *   id = "date_time",
 *   label = @Translation("Date Time (Timestamp field)"),
 *   description = @Translation("Formats a date based the configured date format for timestamp fields.")
 * )
 */
class DateTimeEnhancer extends DateTimeEnhancerBase {

  /**
   * {@inheritdoc}
   */
  public function postProcess($value) {
    $date = new \DateTime();
    $date->setTimestamp($value);
    $configuration = $this->getConfiguration();

    return $date->format($configuration['dateTimeFormat']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareForInput($value) {
    $date = new \DateTime($value);

    return (int) $date->format('U');
  }

}
