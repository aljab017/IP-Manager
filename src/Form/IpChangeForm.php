<?php

namespace Drupal\ip_register\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the IP Change entity edit forms.
 * Add/Remove actions for each IP Range on the organization are presented to the user in a table.
 *
 * @internal
 */
class IpChangeForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'new_ip_change';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /* @var $entity \Drupal\ip_register\Entity\IpChange */
    $entity = $this->entity;
    $form = parent::buildForm($form, $form_state);

    // Allows administrators to hide checkboxes from the Vendors list by disabling registrars in the admin UI.
    foreach (\Drupal::service('entity_type.manager')->getStorage('ip_registrar')->loadMultiple() as $registrar) {
      if ($registrar->get('status')) {
        $registrars_enabled[$registrar->id()] = t($registrar->label());
      }
    }

    $form['registrars']['widget']['#options'] = $registrars_enabled;

    // Hide the disable_notification checkbox if the user is not an administrator
    $user_roles = $this->currentUser()->getRoles();
    if (!in_array("administrator", $user_roles, true)) {
      unset($form['disable_notification']);
      $form['registrars']['widget']['#required'] = true;
    }

    // Display selected vendor registrars at the top of the form.
    foreach ($entity->get('registrars')->referencedEntities() as $registrar) {
      $registrars[] = $registrar->label();
    }

    // Get the organization on this IP Change. This is already set from route parameter during preCreate().
    // @see src/Entity/IpChange.php::preCreate()
    $organization = $entity->get('organization')->entity;

    // Get all IP Range entities that reference the organization on this IP Change.
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $ip_ranges = $entity_type_manager->getStorage('ip_range')
      ->loadByProperties([
          'organization' => $organization->id(),
        ]
      );

    // Build the table only if there are existing IP Ranges on file for the organization.
    if ($ip_ranges) {
      // Render array for HTML table.
      $form['ip_table'] = [
        '#weight' => -60,
        '#type' => 'table',
        '#caption' => $this->t('The action you select for each IP Range will be included in an IP Change email notification sent to the vendors you choose to notify.</p>'),
        '#header' => [
          'action' => $this
            ->t('Action'),
          'ip_range' => $this
            ->t('IP Range'),
          // 'organization' => $this
          //   ->t('Organization'),
        ],
        '#empty' => $this->t('No IP Ranges on file for @organization. Use the <em>Add IP Range</em> button to add a new IP Range.', ['@organization' => $organization->label()]),
      ];

      // Get a list of new IP ranges
      $add_new_ips = [];
      if (isset($entity->add_new_ip) && !empty($entity->add_new_ip->getValue())) {
        // Get the values of the "add_new_ip" field.
        $add_new_ip_values = $entity->add_new_ip->getValue();
        foreach ($add_new_ip_values as $add_new_ip_value) {
          $add_new_ips[] = $add_new_ip_value['target_id'];
        }
      }

      // Build a row in the table for each IP Range.
      foreach ($ip_ranges as $id => $ip_range) {
        // Don't display IP Ranges that were added in this IP Change, new ranges are listed in the Add IP Range field.
        if ($add_new_ips && in_array($ip_range->id(), $add_new_ips)) {
          continue;
        }

        // Display an action select list for every IP Range in the form.
        $form['ip_table'][$id]['action'] = [
          '#type' => 'select',
          '#options' => ['add' => $this->t('Notify Vendor'),
            'remove' => $this->t('Remove')],
          '#empty_option' => $this->t('- No Action -'),
        ];

        // Set default select option for any IP Ranges already on this IP Change.
        $add_ip = array_column($entity->get('add_ip')->getValue(), 'target_id');
        if (in_array($ip_range->id(), $add_ip)) {
          $form['ip_table'][$id]['action']['#default_value'] = 'add';
        }

        $delete_ip = array_column($entity->get('delete_ip')->getValue(), 'target_id');
        if (in_array($ip_range->id(), $delete_ip)) {
          $form['ip_table'][$id]['action']['#default_value'] = 'remove';
        }

        // Display the IP Range in the table.
        $form['ip_table'][$id]['ip_range'] = [
          '#markup' => $this->t('<code>@label</code>', ['@label' => $ip_range->label()]),
        ];

        // Override the delete button
        unset($form['actions']['delete']);
      }
    }

    // Display a Cancel button.
    if (\Drupal::service('current_route_match')->getRouteName() == 'entity.ip_change.edit_form') {
      $cancel_url = new Url('entity.ip_change.delete_form', [
        'ip_change' => $entity->id(),
      ]);
    } else {
      $cancel_url = Url::fromRoute('ip_register.ip_manager', ['myminitex_organization' => $entity->get('organization')->entity->id()]);
    }

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => [
        'class' => ['btn', 'btn-danger']
      ],
      '#url' => $cancel_url,
      '#weight' => 5,
    ];

    // Set text for submit button.
    $form['actions']['submit']['#value'] = $this->t('Continue');

    return $form;
  }

  /**
   * Collects actions from the form table and saves them to the IP Change entity.
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    if ($form_state->getValue('ip_table')) {
      // Build an array keyed by action containing arrays of IDs to act on.
      foreach ($form_state->getValue('ip_table') as $id => $values) {
        if (!empty($values['action'])) {
          $ip_change_list[$values['action']][] = $id;
        }
      }
    }

    // Append any new IP Range entities that were created during this IP change
    // via the IEF widget on the add_new_ip entityreference field.
    if ($new_ips = $form_state->getStorage()['inline_entity_form']['add_new_ip-form']['entities']) {
      foreach ($new_ips as $delta => $field_item) {
        $ip_range = $field_item['entity'];
        // Set the organization on each new IP Range.
        $ip_range->organization->target_id = $entity->organization->entity->id();
        // Set the title on each new IP Range.
        $ip_range->setTitle(inet_ntop($ip_range->get('ip_addresses')->get(0)->ip_start) . ' - ' . inet_ntop($ip_range->get('ip_addresses')->get(0)->ip_end));
        // Save the changes to the IP Range.
        $ip_range->save();
        // Add the IP range to the actions array.
        $ip_change_list['add'][] = $ip_range->id();
      }
    }

    // Set the add_ip entityreference field using our array of IP Range ids that the user selected for Add.
    if (isset($ip_change_list['add'])) {
      // creates an entityreference to each IP Range id in the array
      $entity->add_ip = $ip_change_list['add'];
    }

    // Set the delete_ip entityreference field using our array of IP Range ids that the user selected for Add.
    if (isset($ip_change_list['remove'])) {
      // creates an entityreference to each IP Range id in the array
      $entity->delete_ip = $ip_change_list['remove'];
    }

    if (!(isset($ip_change_list['add']) or (isset($ip_change_list['remove'])))) {
      $this->messenger()->addWarning($this->t('Select at least one IP Range action to continue.'));
      $form_state->setRebuild();
    }

    // Save the IP change entity.
    $result = $entity->save();

    // Record a message in the log.
    $link = $entity->toLink($this->t('View'))->toRenderable();
    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];
    $this->logger('ip_register')->notice('Updated new IP Change %label.', $logger_arguments);

    $form_state->setRedirect('ip_register.ip_change_confirm', [
      'ip_change' => $entity->id()]);
  }
}

