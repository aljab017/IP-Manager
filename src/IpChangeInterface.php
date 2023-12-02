<?php

namespace Drupal\ip_register;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an IP Change entity type.
 */
interface IpChangeInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the IP Change title.
   *
   * @return string
   *   Title of the IP Change.
   */
  public function getTitle();

  /**
   * Sets the IP Change title.
   *
   * @param string $title
   *   The IP Change title.
   *
   * @return \Drupal\ip_register\IpChangeInterface
   *   The called IP Change entity.
   */
  public function setTitle($title);

  /**
   * Gets the IP Change creation timestamp.
   *
   * @return int
   *   Creation timestamp of the IP Change.
   */
  public function getCreatedTime();

  /**
   * Sets the IP Change creation timestamp.
   *
   * @param int $timestamp
   *   The IP Change creation timestamp.
   *
   * @return \Drupal\ip_register\IpChangeInterface
   *   The called IP Change entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the IP Change status.
   *
   * @return bool
   *   TRUE if the IP Change is completed, FALSE otherwise.
   */
  public function isCompleted();

  /**
   * Sets the IP Change status.
   *
   * @param bool $status
   *   TRUE to enable this IP Change, FALSE to disable.
   *
   * @return \Drupal\ip_register\IpChangeInterface
   *   The called IP Change entity.
   */
  public function setStatus($status);

  /**
   * Gets the IP Change email notification body text.
   * 
   * @return string
   *   Body text for the IP Change email notification.
   */
  public function getEmailBody();

  /**
   * Sends emails and sets status on the IP Change entity
   * and all of its referenced IP Range entities.
   * 
   * @return \Drupal\ip_register\IpChangeInterface
   *    The called IP Change entity.
   */
  public function sendRegistrarEmails();

}
