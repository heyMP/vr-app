<?php

namespace Drupal\fieldable_path\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;

/**
 * Defines class for main module logic.
 *
 * @package Drupal\fieldable_path\Controller
 */
class FieldablePathController {

  /**
   * Finds entity for corresponding path and synchronizes values.
   *
   * Updates path value of the 'fieldable_path' field
   * of the entity of the path source.
   *
   * @param string $source
   *   Path source which is getting updated.
   *   Example: /node/1 or /user/123.
   * @param array $path
   *   Array with entity path information.
   */
  public function checkEntityAndSyncPath($source, array $path = []) {

    // Check if current path source matches any entity.
    $params = Url::fromUri('internal:' . $source)->getRouteParameters();

    // If there's no entity matching the route then we
    // do nothing.
    if (empty($params)) {
      return;
    }

    // Load entity from the path source.
    $entity_type = key($params);
    $entity = \Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->load($params[$entity_type]);

    // Make sure the current entity exists.
    if (empty($entity)) {
      return;
    }

    // Make sure the current entity is fieldable.
    if (!($entity instanceof FieldableEntityInterface)) {
      return;
    }

    // Make sure the current entity contains the path field,  otherwise there's
    // nothing to do.
    if (!$entity->hasField('path')) {
      return;
    }

    $this->updateFieldablePath($entity, $path);
  }

  /**
   * Updates fieldable_path(s) with entity's path value.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Drupal entity object.
   * @param array $path
   *   Array with entity path information.
   */
  public function updateFieldablePath(EntityInterface $entity, array $path = []) {

    // Load all 'fieldable_path' fields from entity.
    $fields = $this->getFieldablePathFields($entity);
    $save_entity = FALSE;

    foreach ($fields as $field_name) {
      // There is quite tricky stuff going on here. Normally, we
      // save the field value in field's ::postSave() method,
      // so at this moment field value should already match the
      // path property. However, if someone modifies the path property
      // in the code or outside of entity edit form, we still want to
      // catch it.
      if (!empty($path) && $entity->{$field_name}->value !== $path['alias']) {
        $entity->{$field_name}->value = $path['alias'];
        $save_entity = TRUE;
      }
      elseif (empty($path) && !empty($entity->{$field_name}->value)) {
        $entity->{$field_name} = [];
        $save_entity = TRUE;
      }
    }

    if ($save_entity) {

      // Set temporary flag to the entity which will be available only till the
      // end of request. We do need this flag to avoid unwanted path alias
      // recreation during the node save.
      // See fieldable_path_module_implements_alter() for more info.
      $entity->fieldable_path_save = TRUE;

      // TODO: Is there a good option to save one field only?
      // For devs: if you notice that this code makes yet another entity
      // save call during ongoing insert/save of the same entity, then please
      // report an issue, because this is not the desired behavior and
      // potentially might lead to unpredictable results.
      // The only intention of this entity save is cover cases when path alias
      // gets changed outside of entity save.
      $entity->save();
    }
  }

  /**
   * Calls pathauto entity hooks.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Drupal entity object.
   * @param string $op
   *   String with 'insert' or 'update' value.
   */
  public function pathautoGenerateAlias(EntityInterface $entity, $op) {

    // If the entity was saved within the module, then we don't want to
    // let pathauto to recreate the path alias for this entity.
    if (!empty($entity->fieldable_path_save)) {
      return;
    }

    // If pathauto is not available then there's nothing to do.
    if (!\Drupal::service('module_handler')->moduleExists('pathauto')) {
      return;
    }

    // Call proper pathauto hooks.
    if ($op == 'insert') {
      pathauto_entity_insert($entity);
    }
    elseif ($op == 'update') {
      pathauto_entity_update($entity);
    }
  }

  /**
   * Returns list of 'fieldable_path' fields for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Any Drupal entity object.
   *
   * @return array
   *   List of 'fieldable_path' fields.
   */
  public function getFieldablePathFields(EntityInterface $entity) {
    // Get entity fields list.
    // TODO: Is there a more performant way of getting
    // 'fieldable_path' field from an entity? In theory
    // we could get list of all fields of type 'fieldable_path'
    // and check if any of those exist in the current entity.
    $entity_type = $entity->getEntityTypeId();
    $entity_bundle = $entity->bundle();
    $bundle_fields = \Drupal::entityManager()->getFieldDefinitions($entity_type, $entity_bundle);

    // Loop through the fields and searching for added
    // 'fieldable_path' field.
    $fieldable_path_fields = [];
    foreach ($bundle_fields as $field_name => $field_config) {
      if ($field_config instanceof FieldConfig) {
        if ($field_config->getType() == 'fieldable_path') {
          $fieldable_path_fields[] = $field_name;
        }
      }
    }

    return $fieldable_path_fields;
  }

}
