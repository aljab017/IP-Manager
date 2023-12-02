<?php

namespace Drupal\ip_register\Form;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a confirm form for deleting IP Change entities.
 *
 * @internal
 *
 */
class IpChangeEntityDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    $message = $this->getDeletionMessage();
    $redirect_url = Url::fromRoute('ip_register.ip_manager', ['myminitex_organization' => $entity->get('organization')->entity->id()]);

    // remove any new IP addresses that were added if the user did not submit the form
    if (isset($entity->add_new_ip) && !empty($entity->add_new_ip->getValue())) {
      // Get the values of the "add_new_ip" field.
      $addNewIpValues = $entity->add_new_ip->getValue();
      foreach ($addNewIpValues as $addNewIpValue) {
        // Get the target_id value.
        $targetId = $addNewIpValue['target_id'];
        // Load the referenced entity.
        $ipEntity = \Drupal::entityTypeManager()->getStorage('ip_range')->load($targetId);
        // Delete the referenced ip_range entity.
        $ipEntity?->delete();
      }
    }
    $entity->delete();
    $form_state->setRedirectUrl($redirect_url);
    $this->messenger()->addWarning($message);
    $this->logDeletionMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    return $this->t('Are you sure?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Your changes on this IP Change will not be saved.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('No');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity = $this->getEntity();
    return $entity->toUrl('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    return $this->t('Your IP Change was not applied and has been deleted.');
  }

}
