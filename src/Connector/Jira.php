<?php
/**
 * @file
 * Class that implements a contact form connector to JIRA.
 */

namespace Drupal\contact_form_connect\Connector;

use Drupal\contact_form_connect\ConnectorInterface;
use Drupal\contact_form_connect\Entity\ContactFormConnector;
use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use chobie\Jira\Api;
use chobie\Jira\Api\Authentication\Basic;
use chobie\Jira\Issues\Walker;
use Drupal\contact_form_connect\Encoder\FlatArrayEncoder;
use Drupal\contact_form_connect\Connector\Traits\FetchesFieldLabelsTrait;

/**
 * Implements a contact form connector to JIRA.
 */
class Jira implements ConnectorInterface {
  use StringTranslationTrait;
  use FetchesFieldLabelsTrait;

  protected $connector;
  protected $api;
  protected $encoder;
  protected $verbose = FALSE;

  /**
   * Constructor.
   */
  public function __construct(ContactFormConnector $connector, $connector_config = []) {
    $this->connector = $connector;
    $this->encoder = new FlatArrayEncoder();
    $this->initConnector($connector_config);
  }

  /**
   * Determine whether we should print to Watchdog.
   *
   * @param bool $verbose
   *   Whether we print watchdog entries.
   */
  public function setVerbose($verbose = FALSE) {
    $this->verbose = $verbose;
  }

  /**
   * Generate additional settings.
   */
  public function extraSettingsForm($config = []) {
    $form = [];

    $form['project_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project Key'),
      '#description' => $this->t('Enter the key of the JIRA project to save this issue to.'),
      '#default_value' => (
        !empty($config['project_key']) ?
        $config['project_key'] :
        NULL
      ),
    ];

    $form['issue_summary'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Issue Summary'),
      '#description' => $this->t('Specify the summary of the issue. Tokens allowed!'),
      '#default_value' => (
        !empty($config['issue_summary']) ?
        $config['issue_summary'] :
        NULL
      ),
    ];

    $form['task_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Task Type ID'),
      '#description' => $this->t('Specify the internal ID of the type of task to create.'),
      '#default_value' => (
        !empty($config['task_type']) ?
        $config['task_type'] :
        NULL
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function initConnector($connector_config = []) {
    $this->api = new Api(
      $this->connector->service_endpoint,
      new Basic(
        $this->connector->service_username,
        $this->connector->service_password
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function sendContactMessage(ContactForm $contact_form, array $connector_config = [], Message $contact_message) {

    $token_service = \Drupal::service('token');
    try {
      $created = $this->api->createIssue(
        $connector_config['project_key'],
        $token_service->replace(
          $connector_config['issue_summary'],
          [
            'contact_message' => $contact_message,
            'contact_form' => $contact_form,
          ],
          ['clear' => TRUE]
        ),
        $connector_config['task_type'],
        [
          'description' => $this->buildMessage($contact_message),
        ]
      );
    }
    catch (Exception $e) {
      watchdog_exception('contact_form_connect', $e, 'JIRA: Tried and failed to submit JIRA issue.');
    }
  }

  /**
   * Build a text message.
   */
  protected function buildMessage(Message $contact_message) {
    $remap_headers = $this->fieldLabels($contact_message);

    // Encode the Contact Message as a straight array.
    $encoded_message = \Drupal::service('serializer')->serialize($contact_message, 'flat_array', [
      'skip_keys' => [
        'uuid',
        'langcode',
        'contact_form',
        'copy',
      ],
      'remap_headers' => $remap_headers,
      'reduce_single' => TRUE,
    ]);

    $output = '';
    foreach ($encoded_message as $k => $v) {
      if (!empty($output)) {
        $output .= "\n";
      }
      $output .= $k . ': ' . $v;
    }

    $output .= "\n\n" . $contact_message->url('canonical', ['absolute' => TRUE]) . "\n\n";
    return $output;
  }

}
