<?php

namespace Drupal\ip_register\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Symplify\GitWrapper\GitWrapper;

/**
 * Configure IP Register settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ip_register_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ip_register.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ip_register.settings');

    $form['export_ezproxy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Export IP Ranges to EzProxy configuration text file.'),
      '#default_value' => $config->get('export_ezproxy') ? $config->get('export_ezproxy') : 0,
    ];
    $form['repo_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Root directory'),
      '#description' => $this->t('The root directory where the repository will be cloned. Enter a Drupal filesystem URI beginning with <code>private://</code>'),
      '#default_value' => $config->get('repo_uri') ? $config->get('repo_uri') : '',
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="export_ezproxy"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['output_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Output file path'),
      '#description' => $this->t('Enter a file path relative to the root directory where the EzProxy text file will be saved, e.g., <code>/authentication/AutoLoginIP.txt</code>'),
      '#default_value' => $config->get('output_file') ? $config->get('output_file') : '',
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="export_ezproxy"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['git_deploy_key_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Deploy key'),
      '#description' => $this->t('Enter a server filesystem path to the location of your existing ssh (deploy) key for the remote git repository, e.g. <code>/var/www/.ssh/id_ed25519</code>'),
      '#default_value' => $config->get('git_deploy_key_path') ? $config->get('git_deploy_key_path') : '',
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="export_ezproxy"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['git_remote_ssh'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remote repository'),
      '#description' => $this->t('Enter a git remote repository ssh URL, e.g. <code>git@github.umn.edu:link/ezproxy-files.git</code>'),
      '#default_value' => $config->get('git_remote_ssh') ? $config->get('git_remote_ssh') : '',
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="export_ezproxy"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['git_user_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Git commit user.name'),
      '#description' => $this->t('Enter the name you want as the author of the auto-commits.'),
      '#default_value' => $config->get('git_user_name') ? $config->get('git_user_name') : '',
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="export_ezproxy"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['git_user_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Git commit user.email'),
      '#description' => $this->t('Enter the email you want as the author of the auto-commits.'),
      '#default_value' => $config->get('git_user_email') ? $config->get('git_user_email') : '',
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="export_ezproxy"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('export_ezproxy') AND $repo_uri = $form_state->getValue('repo_uri')) {
      // Ensure that the repository exists (or can be cloned) at the filesystem location provided.
      $file_system = \Drupal::service('file_system');
      $gitWrapper = new GitWrapper();
      $gitWrapper->setPrivateKey($form_state->getValue('git_deploy_key_path'));
      if ($file_system->prepareDirectory($form_state->getValue('repo_uri'))) {
        // Directory already exists, so get a working copy in git.
        $git = $gitWrapper->workingCopy($file_system->realpath($repo_uri));
        // Pull the latest changes from the remote repository.
        $git->pull('origin', 'master');
      } elseif ($file_system->prepareDirectory($form_state->getValue('repo_uri'), FileSystemInterface::CREATE_DIRECTORY)) {
        // Directory has just been created empty, so clone the repository.
        $git = $gitWrapper->cloneRepository($form_state->getValue('git_remote_ssh'), $file_system->realpath($repo_uri));
      } else {
        $form_state->setErrorByName('repo_uri', $this->t('File path error.'));
      }
      // Set the commit author information on the repo.
      $git->config('user.name', $form_state->getValue('git_user_name'));
      $git->config('user.email', $form_state->getValue('git_user_email'));
    } 
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ip_register.settings')
      ->set('export_ezproxy', $form_state->getValue('export_ezproxy'))
      ->set('repo_uri', $form_state->getValue('repo_uri'))
      ->set('output_file', $form_state->getValue('output_file'))
      ->set('git_deploy_key_path', $form_state->getValue('git_deploy_key_path'))
      ->set('git_remote_ssh', $form_state->getValue('git_remote_ssh'))
      ->set('git_user_name', $form_state->getValue('git_user_name'))
      ->set('git_user_email', $form_state->getValue('git_user_email'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
