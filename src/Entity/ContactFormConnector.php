<?php

namespace Drupal\contact_form_connect\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\contact_form_connect\ContactFormConnectorInterface;
use Drupal\contact_form_connect\Connector\Jira;
use Drupal\contact_form_connect\Connector\GoogleSpreadsheet;

/**
 * Defines the Contact Form Connector entity.
 *
 * @ConfigEntityType(
 *   id = "contact_form_connector",
 *   label = @Translation("Contact Form Connector"),
 *   handlers = {
 *     "list_builder" = "Drupal\contact_form_connect\Controller\ContactFormConnectorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\contact_form_connect\Form\ContactFormConnectorForm",
 *       "edit" = "Drupal\contact_form_connect\Form\ContactFormConnectorForm",
 *       "delete" = "Drupal\contact_form_connect\Form\ContactFormConnectorDeleteForm",
 *     }
 *   },
 *   config_prefix = "contact_form_connector",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/contact_form_connector/{contact_form_connector}",
 *     "delete-form" = "/admin/config/system/contact_form_connector/{contact_form_connector}/delete",
 *   }
 * )
 */
class ContactFormConnector extends ConfigEntityBase implements ContactFormConnectorInterface {

  /**
   * The ContactFormConnector ID.
   *
   * @var string
   */
  public $id;

  /**
   * The ContactFormConnector label.
   *
   * @var string
   */
  public $label;

  /**
   * The ContactFormConnector service name.
   *
   * @var string
   */
  public $service_name;

  public function getServiceName() {
    return $this->service_name;
  }
  public function setServiceName($value) {
    $this->service_name = $value;
  }

  /**
   * The ContactFormConnector service endpoint URL.
   *
   * @var string
   */
  public $service_endpoint;

  public function getServiceEndpoint() {
    return $this->service_endpoint;
  }
  public function setServiceEndpoint($value) {
    $this->service_endpoint = $value;
  }

  /**
   * The ContactFormConnector service username.
   *
   * @var string
   */
  public $service_username;

  public function getServiceUsername() {
    return $this->service_username;
  }
  public function setServiceUsername($value) {
    $this->service_username = $value;
  }

  /**
   * The ContactFormConnector service password.
   *
   * @var string
   */
  public $service_password;

  public function getServicePassword() {
    return $this->service_password;
  }
  public function setServicePassword($value) {
    $this->service_password = $value;
  }

  /**
   * Get and initialise the connector class for this config.
   */
  public function getConnector($connector_config) {
    $return = NULL;
    switch ($this->service_name) {
      case 'jira':
        $return = new Jira($this, $connector_config);
        break;

      case 'google_spreadsheet':
        $return = new GoogleSpreadsheet($this, $connector_config);
        break;
    }
    return $return;
  }
}