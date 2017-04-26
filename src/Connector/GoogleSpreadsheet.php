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
use GuzzleHttp\Client;
use Drupal\contact_form_connect\Encoder\FlatArrayEncoder;
use Drupal\contact_form_connect\Connector\Traits\FetchesFieldLabelsTrait;
use Drupal\contact_form_connect\Connector\Traits\ReorganisesTableFieldsTrait;
use Drupal\contact_form_connect\Connector\Traits\TranslatesSpreadsheetColumnsTrait;

/**
 * Implements a contact form connector to JIRA.
 */
class GoogleSpreadsheet implements ConnectorInterface {
  use StringTranslationTrait;
  use FetchesFieldLabelsTrait;
  use ReorganisesTableFieldsTrait;
  use TranslatesSpreadsheetColumnsTrait;

  protected $connector;
  protected $client;
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
   * {@inheritdoc}
   */
  public function extraSettingsForm($config = []) {
    $form = [];

    $form['doc_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Document ID'),
      '#description' => $this->t('Enter the ID of the Google Spreadsheet to add to. You can find it in the URL.'),
      '#default_value' => (
        !empty($config['doc_id']) ?
        $config['doc_id'] :
        NULL
      ),
    ];

    $form['credentials_file_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Credentials File Path'),
      '#description' => $this->t('Enter the filesystem path to the credentials file.'),
      '#default_value' => (
        !empty($config['credentials_file_path']) ?
        $config['credentials_file_path'] :
        NULL
      ),
    ];

    $form['masquerade_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Masquerade as Domain User'),
      '#description' => $this->t('For G Suite domains, enter the email address to masquerade under.'),
      '#default_value' => (
        !empty($config['masquerade_user']) ?
        $config['masquerade_user'] :
        NULL
      ),
    ];

    $form['app_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Name'),
      '#description' => $this->t('Enter the Google app name.'),
      '#default_value' => (
        !empty($config['app_name']) ?
        $config['app_name'] :
        "drupal-contact-form-connect"
      ),
    ];

    $form['sheet_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sheet ID'),
      '#description' => $this->t('Select the ID of the sheet to save to.'),
      '#default_value' => (
        !empty($config['sheet_id']) ?
        $config['sheet_id'] :
        NULL
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function initConnector($connector_config = []) {
    $this->client = new \Google_Client();
    $credentials_file = $connector_config['credentials_file_path'];
    if (!empty($credentials_file) && file_exists($credentials_file)) {
      $this->client->setAuthConfig($credentials_file);
    }
    $app_name = $connector_config['app_name'];
    $this->client->setApplicationName($app_name);
    if (!empty($connector_config['masquerade_user'])) {
      $this->client->setSubject($connector_config['masquerade_user']);
    }
    $this->client->setScopes([
      'https://www.googleapis.com/auth/drive',
      'https://spreadsheets.google.com/feeds',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function sendContactMessage(ContactForm $contact_form, array $connector_config = [], Message $contact_message) {
    $token_array = $this->client->fetchAccessTokenWithAssertion();
    $access_token = $token_array['access_token'];

    // Get the document.
    $service = new \Google_Service_Drive($this->client);
    $sheets_service = new \Google_Service_Sheets($this->client);
    $files = NULL;
    if ($this->verbose) {
      \Drupal::logger('contact_form_connect')->notice('Trying to get spreadsheet file.');
    }
    try {
      $files = $service->files->get($connector_config['doc_id']);
    }
    catch (Exception $e) {
      watchdog_exception('contact_form_connect', $e, 'GoogleSpreadsheet: Failed to fetch file.');
      return;
    }

    // Get the current spreadsheet data.
    if ($this->verbose) {
      \Drupal::logger('contact_form_connect')->notice('Trying to get spreadsheet data.');
    }
    try {
      $sheet_data = $sheets_service->spreadsheets_values->get(
        $connector_config['doc_id'],
        $connector_config['sheet_id']
      );
    }
    catch (Exception $e) {
      watchdog_exception('contact_form_connect', $e, 'GoogleSpreadsheet: Failed to fetch file data.');
      return;
    }

    // Get headers to remap.
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

    if ($this->verbose) {
      \Drupal::logger('contact_form_connect')->notice('Encoded message, trying to compute updates.');
    }

    // Take old data and new row and compute differences.
    $values = $sheet_data->values;
    if (is_null($values)) {
      $values = [];
    }
    $changes = $this->computeUpdate($values, $encoded_message);

    \Drupal::logger('contact_form_connect')->notice(
      ' Inserts: ' . count($changes['insert']) . "\n" .
      ' Updates: ' . count($changes['update']) . "\n"
    );

    if ($this->verbose) {
      \Drupal::logger('contact_form_connect')->notice('Updates computed, time to send. ' . print_r($changes, 1));
    }

    if (!empty($changes['update'])) {
      foreach ($changes['update'] as $row_number => $row) {
        $this->updateRow($row, $sheets_service, $connector_config['doc_id'], $connector_config['sheet_id'], $row_number);
      }
    }
    if (!empty($changes['insert'])) {
      foreach ($changes['insert'] as $row) {
        $this->insertRow($row, $sheets_service, $connector_config['doc_id'], $connector_config['sheet_id']);
      }
    }
  }

  /**
   * Updates an existing row.
   */
  protected function updateRow($row, $sheets_service, $doc_id, $sheet_id, $row_number) {
    $vr = $this->prepareValues($row);

    if ($this->verbose) {
      \Drupal::logger('contact_form_connect')->notice(
        'Trying to update row ' . $row_number . '. ' .
        print_r($vr, 1)
      );
    }
    try {
      $start_header = $sheet_id . '!' . $this->columnToLetter(0) . ($row_number + 1);
      $end_header = $this->columnToLetter(count($row) - 1) . ($row_number + 1);
      $header_range = implode(':', [$start_header, $end_header]);
      $sheets_service->spreadsheets_values->update(
        $doc_id,
        $header_range,
        $vr,
        ['valueInputOption' => 'RAW']
      );
    }
    catch (Exception $e) {
      watchdog_exception(
        'contact_form_connect',
        $e,
        'GoogleSpreadsheet: Failed to update row @number. @data',
        [
          '@row' => $row_number,
          '@data' => print_r($vr, 1),
        ]
      );
    }
  }

  /**
   * Inserts a new row.
   */
  protected function insertRow($row, $sheets_service, $doc_id, $sheet_id) {
    $vr = $this->prepareValues($row);

    if ($this->verbose) {
      \Drupal::logger('contact_form_connect')->notice(
        'Trying to insert row. ' .
        print_r($vr, 1)
      );
    }
    try {
      $sheets_service->spreadsheets_values->append(
        $doc_id,
        $sheet_id,
        $vr,
        [
          'valueInputOption' => 'RAW',
          'insertDataOption' => 'INSERT_ROWS',
        ]
      );
    }
    catch (Exception $e) {
      watchdog_exception(
        'contact_form_connect',
        $e,
        'GoogleSpreadsheet: Failed to insert row. @data',
        [
          '@data' => print_r($vr, 1),
        ]
      );
    }
  }

  /**
   * Preps row by disassociating it.
   */
  protected function prepareValues($row) {
    if ($this->verbose) {
      \Drupal::logger('contact_form_connect')->notice(
        json_encode($row)
      );
    }
    $unassociated = [];
    foreach (array_values($row) as $i) {
      $unassociated[] = (
        !empty($i) ?
        $i :
        ''
      );
    }
    if ($this->verbose) {
      \Drupal::logger('contact_form_connect')->notice(
        json_encode($unassociated)
      );
    }

    $vr = new \Google_Service_Sheets_ValueRange();
    $vr->setValues([$unassociated]);
    return $vr;
  }

}
