<?php

namespace Drupal\ip_register\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\ip_register\IpRangeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the IP Range entity class.
 *
 * @ContentEntityType(
 *   id = "ip_range",
 *   label = @Translation("IP Range"),
 *   label_collection = @Translation("IP Ranges"),
 *   handlers = {
 *     "list_builder" = "Drupal\ip_register\IpRangeListBuilder",
 *     "access" = "Drupal\ip_register\IpRangeAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\ip_register\Form\IpRangeForm",
 *       "edit" = "Drupal\ip_register\Form\IpRangeForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     }
 *   },
 *   base_table = "ip_range",
 *   data_table = "ip_range_field_data",
 *   admin_permission = "administer IP Range",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/myminitex/organization/{myminitex_organization}/ip/range-form",
 *     "canonical" = "/myminitex/ip/range/{ip_range}",
 *     "edit-form" = "/myminitex/ip/range/{ip_range}/edit",
 *     "delete-form" = "/myminitex/ip/range/{ip_range}/delete",
 *     "collection" = "/admin/content/ip-range"
 *   },
 *   field_ui_base_route = "entity.ip_range.settings"
 * )
 */
class IpRange extends ContentEntityBase implements IpRangeInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new IP Range entity is created, set the uid entity reference to
   * the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $route_match = \Drupal::service('current_route_match');
    // Set the organization field from route parameter.
    if ($org_entity = $route_match->getParameter('myminitex_organization')) {
      $values['organization'] = $org_entity->id();
    }
    // Set values on fields.
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
//  Set entity title from IP addresses string.
    if ($this->get('ip_addresses')) {
      $this->setTitle(inet_ntop($this->get('ip_addresses')->get(0)->ip_start) . ' - ' . inet_ntop($this->get('ip_addresses')->get(0)->ip_end));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRegistered() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    // Base field for storing an entityreference to the organization
    $fields['organization'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Organization'))
    ->setDescription(t('The organization registering this IP range.'))
    ->setSetting('target_type', 'myminitex_organization')
    ->setRequired(TRUE)
    ->setDisplayConfigurable('view', TRUE);

    // Base field for storing the IP address data
    $fields['ip_addresses'] = BaseFieldDefinition::create('ipaddress')
    ->setLabel(t('IP Addresses'))
    ->setDescription(t('
      <br>You can enter a range of IP addresses using hyphenation.<br>
        Example: 10.10.10.0 - 10.10.12.255<br>
      <br>You can enter a range of IP addresses using CIDR notation.<br>
        Example: 10.10.10.10/32<br>
      <br>You can enter a single IP address.<br>
        Example: 10.10.10.10
      '))
    ->setCardinality(1)
    ->setDisplayOptions('form', [
      'type' => 'ipaddress',
      'settings' => [
        'display_label' => TRUE,
      ],
    ])
    ->setRequired(TRUE);

    // Base field for storing references to IP Registrar configuration entities
    $fields['registrars'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Registrars'))
    ->setDescription(t('The vendor access lists that will be updated with this IP Range.'))
    ->setSetting('target_type', 'ip_registrar')
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE)
    ->setDisplayOptions('view', [
      'label' => 'inline',
      'type' => 'string',
    ]);
    //
    // Standard entity base fields.
    //
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the IP Range entity.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ]);

      $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the IP Range is registered.'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Registered')
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'inline',
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ]);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Creator'))
      ->setDescription(t('The user who submitted the IP Range.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'author',
        'weight' => 15,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the IP Range was created.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 20,
      ]);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Updated'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the IP Range was last edited.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 20,
      ]);

    return $fields;
  }

}
