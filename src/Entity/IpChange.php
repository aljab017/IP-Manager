<?php

namespace Drupal\ip_register\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;
use Drupal\ip_register\IpChangeInterface;
use \Drupal\Core\Form\FormStateInterface;

/**
 * Defines the IP Change entity class.
 *
 * @ContentEntityType(
 *   id = "ip_change",
 *   label = @Translation("IP Change"),
 *   label_collection = @Translation("IP Changes"),
 *   handlers = {
 *     "list_builder" = "Drupal\ip_register\IpChangeListBuilder",
 *     "access" = "Drupal\ip_register\IpChangeAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\ip_register\Form\IpChangeForm",
 *       "edit" = "Drupal\ip_register\Form\IpChangeForm",
 *       "delete" = "Drupal\ip_register\Form\IpChangeEntityDeleteForm"
 *     }
 *   },
 *   base_table = "ip_change",
 *   admin_permission = "administer IP Change",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/myminitex/organization/{myminitex_organization}/ip/change-form",
 *     "canonical" = "/myminitex/ip/change/{ip_change}",
 *     "edit-form" = "/myminitex/ip/change/{ip_change}/edit",
 *     "delete-form" = "/myminitex/ip/change/{ip_change}/delete",
 *     "collection" = "/admin/content/ip-change"
 *   },
 *
 *   field_ui_base_route = "entity.ip_change.settings"
 * )
 */
class IpChange extends ContentEntityBase implements IpChangeInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new IP Change entity is created, set its base fields using data from the
   * URL query string parameters, the organization route parameter, and the logged-in user.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);

    if ($organization = \Drupal::service('current_route_match')
      ->getParameter('myminitex_organization')) {
      $values['organization'] = $organization->id();
    }

    $user = \Drupal::service('entity_type.manager')->getStorage('user')
      ->load(\Drupal::service('current_user')->id());
    $user_realname = $user->get('field_myminitex_user_realname');

    $values += [
      'registrars' => \Drupal::request()->query->get('notify'),
      'uid' => \Drupal::currentUser()->id(),
      'contact_name' => [
        'given' => $user_realname->get(0)->given,
        'family' => $user_realname->get(0)->family
      ],
      'contact_email' => $user->get('mail')->getValue(),
      'contact_phone' => $user->get('field_myminitex_user_phone')->getValue()
    ];
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
  public function isCompleted() {
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
   * Builds the message body for the email notification.
   */
  public function getEmailBody() {

    // Get values from this IP Change entity to include in the email body.
    $message_replacements = [
      '@organization_name' => $this->get('organization')->entity->label(),
      '@contact_name' => $this->get('contact_name')->get(0)->given . ' ' . $this->get('contact_name')->get(0)->family,
      '@contact_email' => $this->get('contact_email')->get(0)->value,
      '@contact_phone' => $this->get('contact_phone')->get(0)->value,
    ];

    // Include the comment field, if provided.
    if ($comment = $this->get('comment')->getValue()) {
      $message_replacements['@comments'] = t(
'-------------------------------------
Additional Comments:
-------------------------------------
  @comment',
        ['@comment' => $this->get('comment')->get(0)->value]
      );
    } else {
      $message_replacements['@comments'] = '';
    }
    // Load each IP Range entity and insert replacement values
    // into concatenated string for the email body.
    $add_ip = $this->get('add_ip')->referencedEntities();
    $add_new_ip = $this->get('add_new_ip')->referencedEntities();
    $confirm_ip = [];
    // Get an array of IP ranges that are selected to be confirmed.
    foreach ($add_ip as $ip_range) {
      //check if ip_range is in add_new_ip, if not, add it to array called confirm_ip
      if (!in_array($ip_range, $add_new_ip)) {
        $confirm_ip[] = $ip_range;
      }
    }

    if ($confirm_ip) {
      $message_replacements['@confirm_ip'] = t('
-------------------------------------
Confirm IP Ranges:
-------------------------------------');
      foreach ($confirm_ip as $ip_range) {
        $message_replacements['@confirm_ip'] .= t('
  Confirm range: @title (@organization)',
          [
            '@title' => $ip_range->label(),
            '@organization' => $ip_range->get('organization')->entity->label()
          ]
        );
      }
    } else {
      $message_replacements['@confirm_ip'] = '';
    }

    if ($add_new_ip) {
      $message_replacements['@add_new_ip'] = t('
-------------------------------------
Add IP Ranges:
-------------------------------------');
      foreach ($add_new_ip as $ip_range) {
        $message_replacements['@add_new_ip'] .= t('
  Add range: @title (@organization)',
          [
            '@title' => $ip_range->label(),
            '@organization' => $ip_range->get('organization')->entity->label()
          ]
        );
      }
    } else {
      $message_replacements['@add_new_ip'] = '';
    }

    if ($delete_ip = $this->get('delete_ip')->referencedEntities()) {
      $message_replacements['@delete_ip'] = t('
-------------------------------------
Remove IP Ranges:
-------------------------------------');
      foreach ($delete_ip as $ip_range) {
        $message_replacements['@delete_ip'] .= t('
  Remove range: @title (@organization)',
          [
            '@title' => $ip_range->label(),
            '@organization' => $ip_range->get('organization')->entity->label()
          ]
        );
      }
    } else {
      $message_replacements['@delete_ip'] = '';
    }
  return t(
'-------------------------------------
Library Name / Institution: @organization_name
Contact Name: @contact_name
Contact Email: @contact_email
Contact Phone: @contact_phone
@comments@confirm_ip@add_new_ip@delete_ip',
        $message_replacements
      );
  }

  /**
   * Sends the email notification for this IP Change.
   */
  public function sendRegistrarEmails() {

    // Return the entity unchanged if disable_notification is set to true.
    if ($this->disable_notification->value == 1){
      return $this;
    }

    // If this entity status is TRUE, the email has previously been sent,
    // so display a message and return the entity unchanged.
    if ($this->isCompleted()) {
      \Drupal::messenger()->addWarning(t("IP Change @ip_change was already completed.", ['%ip_change' => $this->id()]));
      return $this;
    }
    // Get the email addresses for the selected IP Registrars.
    foreach ($this->get('registrars')->referencedEntities() as $registrar) {
      $BCC[] = $registrar->get('email');
    }
    $BCC[] = "swans062@umn.edu";
    // Prepare params for hook_mail().
    $params['headers']['Bcc'] = implode(",", $BCC);
    $params['subject'] = "IP address changes from Minitex participants";
    $params['body']['description'] = 'This email is official notification of changes to the IP address for the institution listed. This form is generated by Minitex as a convenience to its participants. All correspondence should be with the contact person listed below.';
    $params['body']['message'] = $this->getEmailBody()->render();
    $module = 'ip_register';
    $key = 'ip_register_email_registrars';
    $to = $this->get('contact_email')->get(0)->value;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    // Send the email with Drupal's mail API.
    // @see hook_mail() in ip_register.module
    $send = \Drupal::service('plugin.manager.mail')->mail($module, $key, $to, $langcode, $params, NULL, TRUE);
    // Check if the email sent successfully
    if ($send['result'] === TRUE) {
      // Update logger and show message on page.
      $message = t('Email notification for IP Change #@ip_change sent successfully to the selected vendors.', ['@ip_change' => $this->id()]);
      \Drupal::logger('ip_register')->notice($message);
      \Drupal::messenger()->addStatus($message);
      // Update the registrars list on each IP Range.
      $ip_change_registrars = array_column($this->get('registrars')->getValue(), 'target_id');
      foreach ($this->get('add_ip')->referencedEntities() as $ip_range) {
        // Update list of registrars on each IP Range in the 'add_ip' field of the IP Change.
        $ip_range_existing_registrars = array_column($ip_range->get('registrars')->getValue(), 'target_id');
        $ip_range->registrars = array_merge($ip_range_existing_registrars, $ip_change_registrars);
        $ip_range->save();
      }
      foreach ($this->get('delete_ip')->referencedEntities() as $ip_range) {
        // Update list of registrars on each IP Range in the 'delete_ip' field of the IP Change.
        foreach (array_column($this->get('registrars')->getValue(), 'target_id') as $registrar) {
          $ip_range_existing_registrars = array_column($ip_range->get('registrars')->getValue(), 'target_id');
          $ip_range->registrars = array_diff($ip_range_existing_registrars, $ip_change_registrars);
        }
        $ip_range->save();
      }
      \Drupal::messenger()->addStatus(t('IP Change #@ip_change completed successfully.', ['@ip_change' => $this->id()]));
    }
    // Email send failed. Post an error message via messenger and logger.
    if ($send['result'] !== TRUE) {
      $message = t('There was a problem sending email notification for IP Change #@ip_change. Your changes have NOT been applied. Please contact the system administrator.', ['@ip_change' => $this->id()]);
      \Drupal::messenger()->addError($message);
      \Drupal::logger('ip_register')->error($message);
    }
    // Mailing has been sent and all IP Ranges have been updated accordingly.
    return $this;
  }

  /**
   * Updates the registrars list on each IP Range on this IP Change.
   */
  public function setIpRangeRegistrars() {
    $ip_change_registrars = array_column($this->get('registrars')->getValue(), 'target_id');
    foreach ($this->get('add_ip')->referencedEntities() as $ip_range) {
      // Update list of registrars on each IP Range in the 'add_ip' field of the IP Change.
      $ip_range_existing_registrars = array_column($ip_range->get('registrars')->getValue(), 'target_id');
      $ip_range->registrars = array_merge($ip_range_existing_registrars, $ip_change_registrars);
      // Make the current user the owner of the IP Range.
      $ip_range->Set('uid', \Drupal::currentUser()->id());
      $ip_range->save();
    }
    foreach ($this->get('delete_ip')->referencedEntities() as $ip_range) {
      // Update list of registrars on each IP Range in the 'delete_ip' field of the IP Change.
      foreach (array_column($this->get('registrars')->getValue(), 'target_id') as $registrar) {
        $ip_range_existing_registrars = array_column($ip_range->get('registrars')->getValue(), 'target_id');
        $ip_range->registrars = array_diff($ip_range_existing_registrars, $ip_change_registrars);
      }
      $ip_range->save();
    }
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

     // Base field for storing references to IP ranges configuration entities
    $fields['add_ip'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Add IP Ranges:'))
    ->setDescription(t('IP ranges included in the IP Change operation.'))
    ->setSetting('target_type', 'ip_range')
    ->setSetting('handler', 'default')
    ->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
    ])
    ->setDisplayConfigurable('view', TRUE)
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['add_new_ip'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Add New IP Ranges:'))
    ->setDescription(t('New IP Ranges added here will be included on this IP Change and added to your IP Manager.'))
    ->setSetting('target_type', 'ip_range')
    ->setDisplayOptions('form', [
      'type' => 'inline_entity_form_complex',
      'weight' => -50,
      'settings' => [
        'form_mode' => 'default',
        'override_labels' => TRUE,
        'label_singular' => 'IP Range',
        'label_plural' => 'IP Ranges',
        'allow_new' => TRUE,
        'revision' => FALSE,
        'collapsible' => FALSE,
        'collapsed' => FALSE,
        'allow_existing' => FALSE,
        'allow_duplicate' => FALSE,
      ]
    ])

    ->setSetting('handler', 'default:ip_range')
    ->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
    ])
    ->setDisplayConfigurable('view', TRUE)
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['verify_ip'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Verify Existing Ranges (should already be registered):'))
    ->setDescription(t('IP ranges included in the VERIFY IP Change operation.'))
    ->setSetting('target_type', 'ip_range')
    ->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
    ])
    ->setDisplayConfigurable('view', TRUE)
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['delete_ip'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Delete IP Ranges:'))
    ->setDescription(t('IP ranges included in the Deregister IP Change operation.'))
    ->setSetting('target_type', 'ip_range')
    ->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
    ])
    ->setDisplayConfigurable('view', TRUE)
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Base field for storing references to IP Registrar configuration entities
    $fields['registrars'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Select vendors to notify'))
    ->setDescription(t('Each vendor you select will receive an email notification with your IP Change request.'))
    ->setSetting('target_type', 'ip_registrar')
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setRequired(false)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayOptions('form', [
      'type' => 'checkboxes',
      'weight' => 1,
    ])
    ->setDisplayOptions('view', [
      'label' => 'inline',
      'type' => 'string',
    ])
    ->setDisplayConfigurable('view', TRUE);

    // Base field for storing a boolean to disable email notification
    $fields['disable_notification'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t("Do not notify vendors"))
      ->setDescription(t('You can disable vendor notification by checking this option.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
      ]);

    // Base field for storing a comment with the IP Change Record
    $fields['comment'] = BaseFieldDefinition::create('string_long')
    ->setLabel(t('Comment'))
    ->setDescription(t('You can include a comment to clarify any special instructions for the vendors notified.'))
    ->setDisplayOptions('form', [
      'type' => 'text_textarea',
      'weight' => 10,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayOptions('view', [
      'type' => 'text_default',
      'label' => 'above',
    ]);

    //
    // Base fields for storing IP Change Record contact information
    //

    // Base field for storing an entityreference to the organization
    $fields['organization'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Organization'))
    ->setDescription(t('The organization registering this IP Change.'))
    ->setSetting('target_type', 'myminitex_organization');

    // Contact Name
    $fields['contact_name'] = BaseFieldDefinition::create('name')
    ->setLabel(t('Contact Name'))
    ->setDisplayOptions('form', [
      'type' => 'name_default',
      'weight' => 20,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setSetting('components', [
      'given' => TRUE,
      'family' => TRUE,
      'title' => FALSE,
      'middle' => FALSE,
      'generational' => FALSE,
      'credentials' => FALSE,
    ])
    ->setSetting('labels', [
      'given' => 'First Name',
      'family' => 'Last Name'
    ])
    ->setSetting('widget_layout', 'inline')
    ->setDisplayOptions('view', [
      'type' => 'text_default',
      'label' => 'inline',
    ]);

    // Contact Email
    $fields['contact_email'] = BaseFieldDefinition::create('email')
    ->setLabel(t('Contact Email'))
    ->setDisplayOptions('form', [
      'type' => 'email_default',
      'weight' => 30
    ])
    ->setDisplayOptions('view', [
      'type' => 'text_default',
      'label' => 'inline',
    ]);

    // Contact Phone
    $fields['contact_phone'] = BaseFieldDefinition::create('telephone')
    ->setLabel(t('Contact Phone'))
    ->setDisplayOptions('form', [
      'type' => 'telephone_default',
      'weight' => 40,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayOptions('view', [
      'type' => 'text_default',
      'label' => 'inline',
    ]);

    //
    // Standard base fields.
    //
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the IP Change entity.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the IP Change is completed.'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Completed')
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'inline',
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ]);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Submitted by'))
      ->setDescription(t('The user who submitted the IP Change.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'author',
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Date submitted'))
      ->setDescription(t('The time that the IP Change was created.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
      ]);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the IP Change was last edited.'));

    return $fields;
  }

}
