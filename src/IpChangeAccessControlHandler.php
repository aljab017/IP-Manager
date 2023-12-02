<?php

namespace Drupal\ip_register;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the IP Change entity type.
 */
class IpChangeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view IP Change');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit IP Change', 'administer IP Change'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete IP Change', 'administer IP Change'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create IP Change', 'administer IP Change'], 'OR');
  }

}
