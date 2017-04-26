<?php
/**
 * @file
 * Defines interface for contact form connectors.
 */

namespace Drupal\contact_form_connect;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for contact form connectors.
 */
interface ContactFormConnectorInterface extends ConfigEntityInterface {
	public function getConnector($connector_config);
}
