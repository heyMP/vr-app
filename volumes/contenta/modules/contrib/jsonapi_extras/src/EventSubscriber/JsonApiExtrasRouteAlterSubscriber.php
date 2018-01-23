<?php

namespace Drupal\jsonapi_extras\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for overwrite JSON API routes.
 */
class JsonApiExtrasRouteAlterSubscriber implements EventSubscriberInterface {

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The JSON API resource repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * JsonApiExtrasRouteAlterSubscriber constructor.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepository $resource_type_repository
   *   The JSON API resource repository.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   */
  public function __construct(ResourceTypeRepository $resource_type_repository, ConfigFactoryInterface $config_factory) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->configFactory = $config_factory;
  }

  /**
   * Alters select routes to update the route path.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event to process.
   */
  public function onRoutingRouteAlterSetPaths(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    $prefix = $this->configFactory
      ->get('jsonapi_extras.settings')
      ->get('path_prefix');

    // Overwrite the entry point.
    $path = sprintf('/%s', $prefix);
    $collection->get('jsonapi.resource_list')
      ->setPath($path);

    /** @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType $resource_type */
    foreach ($this->resourceTypeRepository->all() as $resource_type) {
      // Overwrite routes.
      $paths = $this->getPathsForResourceType($resource_type, $prefix);
      foreach ($paths as $route_name => $path) {
        $collection->get($route_name)->setPath($path);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = ['onRoutingRouteAlterSetPaths'];
    return $events;
  }

  /**
   * Returns paths for a resource type.
   *
   * @param \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType $resource_type
   *   The ConfigurableResourceType entity.
   * @param string $prefix
   *   The path prefix.
   *
   * @return array
   *   An array of route paths.
   */
  protected function getPathsForResourceType(ConfigurableResourceType $resource_type, $prefix) {
    $entity_type_id = $resource_type->getEntityTypeId();
    $bundle_id = $resource_type->getBundle();

    // Callback to build the route name.
    $build_route_name = function ($key) use ($resource_type) {
      return sprintf('jsonapi.%s.%s', $resource_type->getTypeName(), $key);
    };

    // Base path.
    $base_path = sprintf('/%s/%s/%s', $prefix, $entity_type_id, $bundle_id);
    if (($resource_config = $resource_type->getJsonapiResourceConfig()) && ($config_path = $resource_config->get('path'))) {
      $base_path = sprintf('/%s/%s', $prefix, $config_path);
    }

    $paths = [];
    $paths[$build_route_name('collection')] = $base_path;
    $paths[$build_route_name('individual')] = sprintf('%s/{%s}', $base_path, $entity_type_id);
    $paths[$build_route_name('related')] = sprintf('%s/{%s}/{related}', $base_path, $entity_type_id);
    $paths[$build_route_name('relationship')] = sprintf('%s/{%s}/relationships/{related}', $base_path, $entity_type_id);

    return $paths;
  }

}
