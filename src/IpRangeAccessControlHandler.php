<?php

namespace Drupal\ip_register;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the IP Range entity type.
 */
class IpRangeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view IP Range');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit IP Range', 'administer IP Range'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete IP Range', 'administer IP Range'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create IP Range', 'administer IP Range'], 'OR');
  }

}
