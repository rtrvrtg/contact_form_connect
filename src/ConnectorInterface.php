<?php
/**
 * @file
 * Define interface for Connectors.
 */

namespace Drupal\contact_form_connect;

use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;

/**
 * Define interface for Connectors.
 */
interface ConnectorInterface {
  /**
   * Generate additional settings.
   */
  function extraSettingsForm($config = []);

  /**
   * Initialise the connector.
   */
  function initConnector($connector_config = []);

  /**
   * Send a Contact Message via the connector.
   */
  function sendContactMessage(ContactForm $contact_form, array $connector_config = [], Message $contact_message);
}
