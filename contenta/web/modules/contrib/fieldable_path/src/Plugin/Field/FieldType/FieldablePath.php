<?php

namespace Drupal\fieldable_path\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'fieldable_path' field type.
 *
 * @FieldType(
 *   id = "fieldable_path",
 *   label = @Translation("Fieldable Path"),
 *   module = "fieldable_path",
 *   description = @Translation("Field for mirroring of 'path' property in entities."),
 *   default_widget = "fieldable_path_widget",
 *   default_formatter = "fieldable_path_formatter"
 * )
 */
class FieldablePath extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
      ],
      'indexes' => [
        // Add index, because the primary purpose of
        // this field is to be filterable.
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // We need to say that our field is not empty
    // in order to get preSave() method executed.
    // TODO: Any drawbacks of doing this?
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Entity path'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {

    // Make sure the entity has path field.
    $entity = $this->getEntity();
    if (!$entity->hasField('path')) {
      return;
    }

    // If the current entity save is caused by the current module,
    // it means that path alias was changed outside of entity edit
    // form, and the field value was already set to the actual in
    // FieldablePathController::updateField(). So there is nothing
    // to do here anymore.
    if (!empty($entity->fieldable_path_save)) {
      return;
    }

    // If Path field is attached to the entity and its value doesn't match
    // Fieldable Path value, simply copy it.
    if (!empty($entity->path->pid) && $this->value != $entity->path->alias) {
      $this->value = $entity->path->alias;
    }

    // When there is Pathauto module is installed then path generation
    // usually happens on entity insert / update hooks, which is not
    // possible to catch with field handlers. So we use pathauto method
    // to generate path alias without saving it into the database.
    if (\Drupal::service('module_handler')->moduleExists('pathauto')) {

      // Below is a small workaround to get entity path alias here.
      // Pathauto requires entity ID to be present on the entity object,
      // because it is designed to attach alias to the certain entity id.
      // In our code here we don't do any savings. All we really need is to
      // get entity path alias ahead of the entity being saved.
      // So we clone entity object to avoid entity id change in the
      // global entity object and set fake id to let pathauto return
      // alias.
      $entity = clone $this->getEntity();
      $entity_type_id = $entity->getEntityTypeId();
      $entity_type_storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
      $entity_type = $entity_type_storage->getEntityType();
      $idKey = $entity_type->getKey('id');
      $entityId = !empty($entity->id()) ? $entity->id() : 0;
      $entity->{$idKey}->value = $entityId;

      // Get entity alias. We are  not saving anything here.
      $alias = \Drupal::service('pathauto.generator')
        ->updateEntityAlias($entity, 'return');

      // If pathauto returns non empty value it means that for this entity
      // path alias will be generated during upcoming entity save hooks, so
      // we just put this value into the field.
      if (!empty($alias)) {
        $this->value = $alias;
      }
    }
  }

}
