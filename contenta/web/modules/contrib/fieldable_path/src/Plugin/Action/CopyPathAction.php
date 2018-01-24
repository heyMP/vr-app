<?php

namespace Drupal\fieldable_path\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Fieldable Path entity update action.
 *
 * @Action(
 *   id = "fieldable_path_copy_path",
 *   label = @Translation("Copy path to Fieldable Path field"),
 * )
 */
class CopyPathAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {

    if (empty($entity)) {
      return;
    }

    if (!$entity->hasField('path')) {
      return;
    }

    // Load entity's internal path (i.e. /node/2).
    $internal_path = $entity->toUrl()->getInternalPath();
    $alias = \Drupal::service('path.alias_manager')
      ->getAliasByPath('/' . $internal_path);

    \Drupal::service('fieldable_path.controller')
      ->updateFieldablePath($entity, ['alias' => $alias]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIfHasPermission($account, 'create url aliases');
    return $return_as_object ? $result : $result->isAllowed();
  }

}
