<?php

namespace Drupal\jsonapi_extras\ResourceType;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi_extras\Entity\JsonapiResourceConfig;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerManager;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines a configurable resource type.
 */
class ConfigurableResourceType extends ResourceType {

  /**
   * The JsonapiResourceConfig entity.
   *
   * @var \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig
   */
  protected $jsonapiResourceConfig;

  /**
   * Plugin manager for enhancers.
   *
   * @var \Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerManager
   */
  protected $enhancerManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Instantiates a ResourceType object.
   *
   * @param string $entity_type_id
   *   An entity type ID.
   * @param string $bundle
   *   A bundle.
   * @param string $deserialization_target_class
   *   The deserialization target class.
   * @param \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig $resource_config
   *   The configuration entity.
   * @param \Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerManager $enhancer_manager
   *   Plugin manager for enhancers.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct($entity_type_id, $bundle, $deserialization_target_class, JsonapiResourceConfig $resource_config, ResourceFieldEnhancerManager $enhancer_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_type_id, $bundle, $deserialization_target_class);

    $this->jsonapiResourceConfig = $resource_config;
    $this->enhancerManager = $enhancer_manager;
    $this->configFactory = $config_factory;

    if ($resource_config->get('resourceType')) {
      // Set the type name.
      $this->typeName = $resource_config->get('resourceType');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicName($field_name) {
    return $this->translateFieldName($field_name, 'fieldName', 'publicName');
  }

  /**
   * {@inheritdoc}
   */
  public function getInternalName($field_name) {
    return $this->translateFieldName($field_name, 'publicName', 'fieldName');
  }

  /**
   * Returns the jsonapi_resource_config.
   *
   * @return \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig
   *   The jsonapi_resource_config entity.
   */
  public function getJsonapiResourceConfig() {
    return $this->jsonapiResourceConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldEnabled($field_name) {
    $resource_field = $this->getResourceFieldConfiguration($field_name);
    return $resource_field
      ? empty($resource_field['disabled'])
      : parent::isFieldEnabled($field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function includeCount() {
    return $this->configFactory
      ->get('jsonapi_extras.settings')
      ->get('include_count');
  }

  /**
   * Get the resource field configuration.
   *
   * @param string $field_name
   *   The internal field name.
   * @param string $from
   *   The realm of the provided field name.
   *
   * @return array
   *   The resource field definition. NULL if none can be found.
   */
  public function getResourceFieldConfiguration($field_name, $from = 'fieldName') {
    $resource_fields = $this->jsonapiResourceConfig->get('resourceFields');
    // Find the resource field in the config entity for the given field name.
    $found = array_filter($resource_fields, function ($resource_field) use ($field_name, $from) {
      return !empty($resource_field[$from]) &&
        $field_name == $resource_field[$from];
    });
    if (empty($found)) {
      return NULL;
    }
    return reset($found);
  }

  /**
   * Get the field enhancer plugin.
   *
   * @param string $field_name
   *   The internal field name.
   * @param string $from
   *   The realm of the provided field name.
   *
   * @return \Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerInterface|null
   *   The enhancer plugin. NULL if not found.
   */
  public function getFieldEnhancer($field_name, $from = 'fieldName') {
    if (!$resource_field = $this->getResourceFieldConfiguration($field_name, $from)) {
      return NULL;
    }
    if (empty($resource_field['enhancer']['id'])) {
      return NULL;
    }
    try {
      $enhancer_info = $resource_field['enhancer'];
      // Ensure that the settings are in a suitable format.
      $settings = [];
      if (!empty($enhancer_info['settings']) && is_array($enhancer_info['settings'])) {
        $settings = $enhancer_info['settings'];
      }
      // Get the enhancer instance.
      /** @var \Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerInterface $enhancer */
      $enhancer = $this->enhancerManager->createInstance(
        $enhancer_info['id'],
        $settings
      );
      return $enhancer;
    }
    catch (PluginNotFoundException $exception) {
      return NULL;
    }

  }

  /**
   * Given the internal or public field name, get the other one.
   *
   * @param string $field_name
   *   The name of the field.
   * @param string $from
   *   The realm of the provided field name.
   * @param string $to
   *   The realm of the desired field name.
   *
   * @return string
   *   The field name in the desired realm.
   */
  private function translateFieldName($field_name, $from, $to) {
    $resource_field = $this->getResourceFieldConfiguration($field_name, $from);
    return empty($resource_field[$to])
      ? $field_name
      : $resource_field[$to];
  }

}
