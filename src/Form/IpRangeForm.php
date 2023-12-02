<?php

namespace Drupal\ip_register\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the IP Range entity edit forms.
 */
class IpRangeForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $entity->setTitle(inet_ntop($entity->get('ip_addresses')->get(0)->ip_start) . ' - ' . inet_ntop($entity->get('ip_addresses')->get(0)->ip_end));
    $result = $entity->save();

    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New IP Range %label has been created.', $message_arguments));
      $this->logger('ip_register')->notice('Created new IP Range %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The IP Range %label has been updated.', $message_arguments));
      $this->logger('ip_register')->notice('Updated new IP Range %label.', $logger_arguments);
    }

    if ($organization = \Drupal::service('current_route_match')->getParameter('myminitex_organization')) {
      $form_state->setRedirect('ip_register.ip_manager', ['myminitex_organization' => $organization->id()]);
    } else {
      $form_state->setRedirect('entity.ip_range.canonical', ['ip_range' => $entity->id()]);
    }
  }

}
