<?php

namespace Drupal\graphql\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\graphql\GraphQL\Schema\SchemaLoader;
use Drupal\graphql\Plugin\GraphQL\SchemaPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SchemaOverviewController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The schema plugin manager service.
   *
   * @var \Drupal\graphql\Plugin\GraphQL\SchemaPluginManager
   */
  protected $schemaManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a SchemaOverviewController object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler srevice.
   * @param \Drupal\graphql\Plugin\GraphQL\SchemaPluginManager $schemaManager
   *   The schema plugin manager service.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, SchemaPluginManager $schemaManager) {
    $this->schemaManager = $schemaManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('plugin.manager.graphql.schema')
    );
  }

  /**
   * Renders a table of available schemas.
   *
   * @return array
   *   The renderable array for the overview table.
   */
  public function listSchemas() {
    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Schema'),
        $this->t('Provider'),
        $this->t('Operations'),
      ],
      '#attributes' => [
        'id' => 'graphql-schemas',
      ],
    ];

    foreach ($this->schemaManager->getDefinitions() as $key => $definition) {
      $table["schema:$key"]['name'] = [
        '#plain_text' => $definition['name'],
      ];

      $table["schema:$key"]['provider'] = [
        '#plain_text' => $this->moduleHandler->getName($definition['provider']),
      ];

      $table["schema:$key"]['operations'] = $this->buildOperations($key, $definition);
    }

    return $table;
  }

  /**
   * Builds a renderable list of operation links for a schema.
   *
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   *
   * @return array
   *   A renderable array of operation links.
   */
  protected function buildOperations($pluginId, array $pluginDefinition) {
    $build = [
      '#type' => 'operations',
      '#links' => $this->getOperations($pluginId, $pluginDefinition),
    ];

    return $build;
  }

  /**
   * Provides an array of information to build a list of operation links.
   *
   * @param $pluginId
   *   The plugin id.
   * @param $pluginDefinition
   *   The plugin definition array.
   *
   * @return array
   *   An associative array of operation link data for this list, keyed by
   *   operation name, containing the following key-value pairs:
   *     - title: The localized title of the operation.
   *     - url: An instance of \Drupal\Core\Url for the operation URL.
   *     - weight: The weight of this operation.
   */
  protected function getOperations($pluginId, $pluginDefinition) {
    $operations = $this->moduleHandler->invokeAll('graphql_schema_operations', [$pluginId, $pluginDefinition]);
    $this->moduleHandler->alter('graphql_schema_operations', $operations, $pluginId, $pluginDefinition);
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $operations;
  }
}
