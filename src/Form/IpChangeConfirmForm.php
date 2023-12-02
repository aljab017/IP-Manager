<?php

namespace Drupal\ip_register\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class IpChangeConfirmForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ip_register_ip_change_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Submit IP Change');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('<p>Review your IP Change and click %confirm_text below.</p>', ['%confirm_text' => $this->getConfirmText()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Submit');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $route_match = \Drupal::service('current_route_match');
    $entity = $route_match->getParameter('ip_change');
    return new Url('entity.ip_change.delete_form', [
      'ip_change' => $entity->id()
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $route_match = \Drupal::service('current_route_match');
    $entity = $route_match->getParameter('ip_change');
    // Display the actual email body text that will be sent with this IP Change email notification.
    $form['summary']['email_body'] = ['#markup' => $this->t('
    <div class="card">
      <div class="card-header">
        Preview IP Change
      </div>
      <div class="card-body">
        <pre>@email_body</pre>
      </div>
    </div>', [
      '@ip_change' => $entity->id(),
      '@email_body' => $entity->getEmailBody(),
      ])
    ];

    // Collect a list of IP Registrar labels from the registrars entityreference field on this IP Change.
    $registrars = [];
    foreach ($entity->get('registrars')->referencedEntities() as $registrar) {
      $registrars[] = $registrar->label();
    }

    // Display the selected registrars who will receive this IP Change.
    if (!empty($registrars) && $entity->disable_notification->value == 0) {
      $form['summary']['registrars'] = ['#markup' => $this->t('<p><small class="text-muted">An email notification containing this IP Change will be sent to @registrars.</small></p>',
        ['@registrars' => implode(", ", $registrars)]
      )];
    }
    else {
      $form['summary']['registrars'] = ['#markup' => $this->t('<p><small class="text-muted">Vendors will not be notified of this IP Change.</small></p>')];
    }

    // Include a back button to the IP Change edit_form.
    $form['actions']['back'] = [
      '#type' => 'link',
      '#title' => $this->t('Go Back'),
      '#attributes' => [
        'class' => ['btn', 'btn-secondary', 'mr-1']
      ],
      // '#url' => $entity->toUrl('add-form', $routeParameters)->toString(),
      '#url' => $entity->toUrl('edit-form'),
      '#weight' => 5,
    ];

    // Set classes on cancel link.
    $form['actions']['cancel']['#attributes']['class'] = ['btn', 'btn-danger'];
    $form['actions']['cancel']['#weight'] = 10;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::service('config.factory')->get('ip_register.settings');
    $route_match = \Drupal::service('current_route_match');
    $entity = $route_match->getParameter('ip_change');

    // Send the email and update statuses on IP Change and its referenced IP Range entities.
    $entity->sendRegistrarEmails();
    $entity->setIpRangeRegistrars();
    // remove IP ranges that were removed from the IP Change.
    if (isset($entity->delete_ip)) {
      // Get the values of the "delete_ip" field.
      $removeIpValues = $entity->delete_ip->getValue();
      foreach ($removeIpValues as $removeIpValue) {
        // Get the target_id value.
        $targetId = $removeIpValue['target_id'];
        // Load the referenced entity.
        $ipEntity = \Drupal::entityTypeManager()->getStorage('ip_range')->load($targetId);
        // Delete the referenced ip_range entity.
        $ipEntity?->delete();
      }
    }
    // Run export if configured.
    if ($config->get('export_ezproxy')) {
      \Drupal::service('ip_register.export')->toEzProxy($entity);
    }
    $entity->save();
    // Now we can set the status field to TRUE on this IP Change.
    $entity->setStatus(TRUE);
    // Take the user to the IP Manager home dashboard.
    $form_state->setRedirect('ip_register.ip_manager', ['myminitex_organization' => $entity->get('organization')->entity->id()]);
  }
}
