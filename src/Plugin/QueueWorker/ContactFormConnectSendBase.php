<?php
/**
 * @file
 * Contains Drupal\contact_form_connect\Plugin\QueueWorker\ContactFormConnectSendBase.php
 */

namespace Drupal\contact_form_connect\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for the NodePublish Queue Workers.
 */
abstract class ContactFormConnectSendBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The Contact Form storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $contactFormStorage;

  /**
   * The Contact Form Connector storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $contactFormConnectorStorage;

  /**
   * The Message storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $messageStorage;

  /**
   * Creates a new ContactFormConnectSendBase object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $cf_storage
   *   The Contact Form storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $cfc_storage
   *   The Contact Form Connector storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $message_storage
   *   The Message storage.
   */
  public function __construct(EntityStorageInterface $cf_storage, EntityStorageInterface $cfc_storage, EntityStorageInterface $message_storage) {
    $this->contactFormStorage = $cf_storage;
    $this->contactFormConnectorStorage = $cfc_storage;
    $this->messageStorage = $message_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('contact_form'),
      $container->get('entity.manager')->getStorage('contact_form_connector'),
      $container->get('entity.manager')->getStorage('contact_message')
    );
  }

  /**
   * Publishes a node.
   *
   * @param NodeInterface $node
   * @return int
   */
  protected function publishNode($node) {
    $node->setPublished(TRUE);
    return $node->save();
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var NodeInterface $node */

    $connector = $this->contactFormConnectorStorage->load($data->contact_form_connector_type);
    $contact_form = $this->contactFormStorage->load($data->contact_form_id);
    $connector_config = $data->connector_config;
    $message = $this->messageStorage->load($data->message_id);

    if (!empty($connector) && !empty($contact_form) && !empty($message)) {
      $connect = $connector->getConnector($connector_config);
      $connect->sendContactMessage($contact_form, $connector_config, $message);
    }
  }
}