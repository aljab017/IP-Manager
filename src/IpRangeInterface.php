<?php

namespace Drupal\ip_register;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an IP Range entity type.
 */
interface IpRangeInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the IP Range title.
   *
   * @return string
   *   Title of the IP Range.
   */
  public function getTitle();

  /**
   * Sets the IP Range title.
   *
   * @param string $title
   *   The IP Range title.
   *
   * @return \Drupal\ip_register\IpRangeInterface
   *   The called IP Range entity.
   */
  public function setTitle($title);

  /**
   * Gets the IP Range creation timestamp.
   *
   * @return int
   *   Creation timestamp of the IP Range.
   */
  public function getCreatedTime();

  /**
   * Sets the IP Range creation timestamp.
   *
   * @param int $timestamp
   *   The IP Range creation timestamp.
   *
   * @return \Drupal\ip_register\IpRangeInterface
   *   The called IP Range entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the IP Range status.
   *
   * @return bool
   *   TRUE if the IP Range has been registered in a previous IP Change, FALSE otherwise.
   */
  public function isRegistered();

  /**
   * Sets the IP Range status.
   *
   * @param bool $status
   *   TRUE to enable this IP Range, FALSE to disable.
   *
   * @return \Drupal\ip_register\IpRangeInterface
   *   The called IP Range entity.
   */
  public function setStatus($status);

}
