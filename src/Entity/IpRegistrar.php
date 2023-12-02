<?php

namespace Drupal\ip_register\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\ip_register\IpRegistrarInterface;

/**
 * Defines the IP Registrar entity type.
 *
 * @ConfigEntityType(
 *   id = "ip_registrar",
 *   label = @Translation("IP Registrar"),
 *   label_collection = @Translation("IP Registrars"),
 *   label_singular = @Translation("IP Registrar"),
 *   label_plural = @Translation("IP Registrars"),
 *   label_count = @PluralTranslation(
 *     singular = "@count IP Registrar",
 *     plural = "@count IP Registrars",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\ip_register\IpRegistrarListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ip_register\Form\IpRegistrarForm",
 *       "edit" = "Drupal\ip_register\Form\IpRegistrarForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "ip_registrar",
 *   admin_permission = "administer IP Registrar",
 *   links = {
 *     "collection" = "/admin/structure/ip-registrar",
 *     "add-form" = "/admin/structure/ip-registrar/add",
 *     "edit-form" = "/admin/structure/ip-registrar/{ip_registrar}",
 *     "delete-form" = "/admin/structure/ip-registrar/{ip_registrar}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "email"
 *   }
 * )
 */

class IpRegistrar extends ConfigEntityBase implements IpRegistrarInterface {

  /**
   * The IP Registrar ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The IP Registrar label.
   *
   * @var string
   */
  protected $label;

  /**
   * The IP Registrar status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The ip_registrar description.
   *
   * @var string
   */
  protected $description;

  /**
   * The ip_registrar contact email.
   *
   * @var string
   */
  protected $email;
}
