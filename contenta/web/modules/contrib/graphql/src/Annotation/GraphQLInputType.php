<?php

namespace Drupal\graphql\Annotation;

/**
 * Annotation for GraphQL input type plugins.
 *
 * @Annotation
 */
class GraphQLInputType extends GraphQLAnnotationBase {

  /**
   * {@inheritdoc}
   */
  public $pluginType = GRAPHQL_INPUT_TYPE_PLUGIN;

  /**
   * List of input fields.
   *
   * @var array
   */
  public $fields = [];

}
