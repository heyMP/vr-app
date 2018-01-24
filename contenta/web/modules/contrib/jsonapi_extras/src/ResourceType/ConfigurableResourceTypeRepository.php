<?php

namespace Drupal\jsonapi_extras\ResourceType;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerManager;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a repository of JSON API configurable resource types.
 */
class ConfigurableResourceTypeRepository extends ResourceTypeRepository {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

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
   * A list of resource types.
   *
   * @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType[]
   */
  protected $resourceTypes;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_manager, EntityRepositoryInterface $entity_repository, ResourceFieldEnhancerManager $enhancer_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_type_manager, $bundle_manager);
    $this->entityRepository = $entity_repository;
    $this->enhancerManager = $enhancer_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function all() {
    if (!$this->all) {
      $this->all = $this->getResourceTypes(FALSE);
    }
    return $this->all;
  }

  /**
   * {@inheritdoc}
   */
  public function get($entity_type_id, $bundle) {
    if (empty($entity_type_id)) {
      throw new PreconditionFailedHttpException('Server error. The current route is malformed.');
    }

    foreach ($this->getResourceTypes(FALSE) as $resource) {
      if ($resource->getEntityTypeId() == $entity_type_id && $resource->getBundle() == $bundle) {
        return $resource;
      }
    }

    return NULL;
  }

  /**
   * Returns an array of resource types.
   *
   * @param bool $include_disabled
   *   TRUE to included disabled resource types.
   *
   * @return array
   *   An array of resource types.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getResourceTypes($include_disabled = TRUE) {
    if (isset($this->resourceTypes) && $include_disabled) {
      return $this->resourceTypes;
    }

    $entity_type_ids = array_keys($this->entityTypeManager->getDefinitions());

    $resource_types = [];

    $resource_config_ids = [];
    foreach ($entity_type_ids as $entity_type_id) {
      $bundles = array_keys($this->bundleManager->getBundleInfo($entity_type_id));
      $resource_config_ids = array_merge($resource_config_ids, array_map(function ($bundle) use ($entity_type_id) {
        return sprintf('%s--%s', $entity_type_id, $bundle);
      }, $bundles));
    }

    $resource_configs = $this->entityTypeManager->getStorage('jsonapi_resource_config')->loadMultiple($resource_config_ids);

    if (isset($this->resourceTypes) && !$include_disabled) {
      return $this->filterOutDisabledResourceTypes($this->resourceTypes, $resource_configs);
    }

    foreach ($entity_type_ids as $entity_type_id) {
      $bundles = array_keys($this->bundleManager->getBundleInfo($entity_type_id));
      $current_types = array_map(function ($bundle) use ($entity_type_id, $include_disabled, $resource_configs) {
        $resource_config_id = sprintf('%s--%s', $entity_type_id, $bundle);
        $resource_config = isset($resource_configs[$resource_config_id]) ? $resource_configs[$resource_config_id] : new NullJsonapiResourceConfig([], '');
        return new ConfigurableResourceType(
          $entity_type_id,
          $bundle,
          $this->entityTypeManager->getDefinition($entity_type_id)->getClass(),
          $resource_config,
          $this->enhancerManager,
          $this->configFactory
        );
      }, $bundles);
      $resource_types = array_merge($resource_types, $current_types);
    }

    $this->resourceTypes = $resource_types;
    if (!$include_disabled) {
      return $this->filterOutDisabledResourceTypes($this->resourceTypes, $resource_configs);
    }
    return $this->resourceTypes;
  }

  /**
   * Takes a list of resource types and removes the disabled from it.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The list of resource types including disabled ones.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $resource_configs
   *   The configuration entities that accompany the resource types.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType[]
   *   The list of enabled resource types.
   */
  protected function filterOutDisabledResourceTypes($resource_types, $resource_configs) {
    return array_filter($resource_types, function (ResourceType $resource_type) use ($resource_configs) {
      $resource_config_id = sprintf(
        '%s--%s',
        $resource_type->getEntityTypeId(),
        $resource_type->getBundle()
      );
      $resource_config = isset($resource_configs[$resource_config_id]) ? $resource_configs[$resource_config_id] : new NullJsonapiResourceConfig([], '');
      return !$resource_config->get('disabled');
    });
  }

}
