<?php

namespace Drupal\contact_form_connect\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Contact Form Connector add and edit forms.
 */
class ContactFormConnectorForm extends EntityForm {

  /**
   * Constructs an ContactFormConnectorForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $connector = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $connector->label(),
      '#description' => $this->t("Label for the Contact Form Connector."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $connector->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$connector->isNew(),
    ];

    $form['service_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Service Name'),
      '#default_value' => $connector->getServiceName(),
      '#description' => $this->t("Name of the service."),
      '#options' => [
        'jira' => $this->t('JIRA'),
        'google_spreadsheet' => $this->t('Google Spreadsheet'),
      ],
      '#required' => TRUE,
    ];

    $form['service_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service endpoint URL'),
      '#default_value' => $connector->getServiceEndpoint(),
    ];

    $form['service_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service username'),
      '#default_value' => $connector->getServiceUsername(),
    ];

    $form['service_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Service password'),
      '#default_value' => $connector->getServicePassword(),
    ];

    // You will need additional form elements for your custom properties.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $connector = $this->entity;
    $status = $connector->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label Contact Form Connector.', array(
        '%label' => $connector->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label Contact Form Connector was not saved.', array(
        '%label' => $connector->label(),
      )));
    }

    $form_state->setRedirect('entity.contact_form_connector.collection');
  }

  /**
   * Helper function to check whether an Contact Form Connector configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityQuery->get('contact_form_connector')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
