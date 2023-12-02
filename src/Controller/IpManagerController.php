<?php

namespace Drupal\ip_register\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for IP Register routes.
 */
class IpManagerController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the response.
   */
  public function build() {
    $organization = $this->routeMatch->getParameter('myminitex_organization');
    // Get all IP registration entities that reference this organization.
    $ip_ranges = $this->entityTypeManager->getStorage('ip_range')
    ->loadByProperties([
      'organization' => $organization->id()
      ]
    );

    // Initialize the IP table element.
    $build['ip_table'] = [
      '#type' => 'table',
      '#attributes' => ['class' => ['custom-ip-table']],
      '#caption' => $this->t('IP address ranges for @organization are shown here. Use the <a href=":url">IP Change Form</a> to make changes and notify vendors.',
        [
          '@organization' => $organization->label(),
          ':url' => Url::fromRoute('entity.ip_change.add_form', ['myminitex_organization' => $organization->id()])->toString()
        ]),
      '#header' => [
        'ip_start' => $this
          ->t('Start IP'),
        'ip_end' => $this
          ->t('End IP'),
        'status' => $this
          ->t('Status'),
        'vendors_notified' => $this
          ->t('Vendors notified'),
        'date_modified' => $this
          ->t('Modified on'),
        'user_modified' => $this
          ->t('Modified by'),
      ],
      '#empty' => $this->t('
      No IP Ranges on file for @organization. Use the <a href=":url">IP Change form</a> to get started.', [
        '@organization' => $organization->label(),
        ':url' => Url::fromRoute('entity.ip_change.add_form', ['myminitex_organization' => $organization->id()])->toString(),
      ])
    ];

    foreach ($ip_ranges as $key => $entity) {
      // Build the rest of the table columns.
      $build['ip_table'][$key]['ip_start'] = [
        '#markup' => $this->t('<code>@ip_start</code>', ['@ip_start' => inet_ntop($entity->get('ip_addresses')->get(0)->ip_start)]),
      ];
      $build['ip_table'][$key]['ip_end'] = [
        '#markup' => $this->t('<code>@ip_end</code>', ['@ip_end' => inet_ntop($entity->get('ip_addresses')->get(0)->ip_end)]),
      ];
      // Get the list of IP Registrars referenced by this IP Range and display them in the table.
      if ($registrars = $entity->get('registrars')->referencedEntities()) {
        foreach ($registrars as $ip_registrar) {
          $registered[$ip_registrar->id()] = $ip_registrar->label();
        }
      }
      if (isset($registered)) {
        $build['ip_table'][$key]['status'] = [
          '#markup' => $this->t('<span class="badge badge-success" >Registered</span>'),
        ];
        // Include a link to view the registered IP Range full entity.
        // if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
        //   $build['ip_table'][$key]['link'] = [
        //     '#type' => 'link',
        //     '#title' => $this->t('View'),
        //     '#url' => $entity->toUrl('canonical')// Url::fromRoute('entity.ip_range.canonical', ['ip_range' => $entity->id()]),
        //   ];
        // }
      } else {
          $build['ip_table'][$key]['status'] = [
            '#markup' => $this->t('<span class="badge badge-secondary">Disabled</span>'),
          ];
        // Include a link to remove/cancel/delete IP Ranges that are pending.
        // if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
        //   $build['ip_table'][$key]['link'] = [
        //     '#type' => 'link',
        //     '#title' => $this->t('Delete'),
        //     '#url' => $entity->toUrl('delete-form'),
        //   ];
        // }
      }
      // Get the list of IP Registrars referenced by this IP Range and display them in the table.
      if ($registrars = $entity->get('registrars')->referencedEntities()) {
        $vendors_notified = [];
        foreach($registrars as $ip_registrar){
          if(!in_array($ip_registrar->label(), $vendors_notified)){
            $vendors_notified[] = $ip_registrar->label();
          }
        }
        if(isset($vendors_notified)){
          $registrars_string = implode('</li><li>', $vendors_notified);
          $registrars_markup = '<div class="vendors-list"><li>' . $registrars_string . '</li></div>';
          $build['ip_table'][$key]['vendors_notified'] = [
            '#markup' => $registrars_markup,
          ];
        }
      }
      else {
        $build['ip_table'][$key]['vendors_notified'] = [
          '#plain_text' => 'N/A',
        ];
      }
      $build['ip_table'][$key]['date_modified'] = [
        '#plain_text' => date('n/j/Y h:i A', $entity->get('changed')->value),
      ];
      $build['ip_table'][$key]['user_modified'] = [
        '#plain_text' => $entity->getOwner()->getDisplayName(),
      ];
    }

    return $build;
  }

}
