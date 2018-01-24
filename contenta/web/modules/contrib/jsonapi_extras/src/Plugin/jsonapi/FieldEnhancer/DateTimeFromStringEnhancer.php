<?php

namespace Drupal\jsonapi_extras\Plugin\jsonapi\FieldEnhancer;

use Drupal\jsonapi_extras\Plugin\DateTimeEnhancerBase;

/**
 * Perform additional manipulations to datetime fields.
 *
 * @ResourceFieldEnhancer(
 *   id = "date_time_from_string",
 *   label = @Translation("Date Time (Date Time field)"),
 *   description = @Translation("Formats a date based the configured date format for date fields.")
 * )
 */
class DateTimeFromStringEnhancer extends DateTimeEnhancerBase {

  /**
   * {@inheritdoc}
   */
  public function postProcess($value) {
    $storage_timezone = new \DateTimezone(DATETIME_STORAGE_TIMEZONE);
    $date = new \DateTime($value, $storage_timezone);

    $configuration = $this->getConfiguration();

    $output_timezone = new \DateTimezone(drupal_get_user_timezone());
    $date->setTimezone($output_timezone);

    return $date->format($configuration['dateTimeFormat']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareForInput($value) {
    $date = new \DateTime($value);

    // Adjust the date for storage.
    $storage_timezone = new \DateTimezone(DATETIME_STORAGE_TIMEZONE);
    $date->setTimezone($storage_timezone);

    return $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
  }

}
