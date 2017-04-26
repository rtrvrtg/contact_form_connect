<?php
/**
 * @file
 * Contains \Drupal\contact_tweak\Form\ContactTweakForm.
 */

namespace Drupal\contact_form_connect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\contact\ContactFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\NestedArray;
use Drupal\contact_form_connect\Entity\ContactFormConnector;

/**
 * Defines a form to configure maintenance settings for this site.
 */
class ContactFormConnectInstanceForm extends ConfigFormBase {

  protected $contactForm;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'contact_form_connect_instance_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['contact_form_connect.settings'];
  }

  /**
   * Gets all available connectors.
   */
  protected function getAllConnectorOptions() {
    $query = \Drupal::service('entity.query')->get('contact_form_connector');
    $entity_ids = $query->execute();
    $connectors = ContactFormConnector::loadMultiple($entity_ids);
    $return = [];
    foreach ($connectors as $connector) {
      $return[$connector->id()] = $connector->label();
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('contact_form_connect.settings');
    $build_info = $form_state->getBuildInfo();
    $form_id = $build_info['args'][0]['contactFormEntity']->id();

    $req_key = time();

    // Get list of connectors. From form state if possible,
    // and then falling back to the config.
    $connectors = [];
    $maybe_fs_conn = $form_state->get(['contact_form_connectors']);
    if (!empty($maybe_fs_conn)) {
      // drupal_set_message('loaded from form state ' . $req_key);
      // drupal_set_message(print_r($maybe_fs_conn, 1));
      $connectors = $maybe_fs_conn;
    }
    if (empty($maybe_fs_conn)) {
      // drupal_set_message('loaded from config ' . $req_key);
      $connectors = $config->get($form_id . '.contact_form_connectors');
    }

    // Detect button clicks and update form state accordingly.
    $maybe_triggering = $form_state->getTriggeringElement();
    if (!empty($maybe_triggering)) {
      // drupal_set_message($maybe_triggering['#name']);
      $trigger_name = $maybe_triggering['#name'];
      if ($trigger_name == '__add_connector__') {
        $connectors[] = [];
      }
      elseif (preg_match('/^__remove_connector__(.+)$/', $trigger_name, $trigger_match)) {
        // drupal_set_message(print_r($trigger_match, 1));
        $index = intval($trigger_match[1]);
        unset($connectors[$index]);
        $connectors = array_values($connectors);
      }
    }

    // drupal_set_message('saved to form state ' . $req_key);
    $form_state->set(['contact_form_connectors'], $connectors);

    $form['connectors'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="connectors-list">',
      '#suffix' => '</div>',
    ];

    // drupal_set_message(print_r($connectors, 1));

    foreach ($connectors as $key => $connector_config) {
      $form['connectors'][$key] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Connector #%item', ['%item' => $key + 1]),
      ];

      $connector_id = $connector_config['type'];

      $form['connectors'][$key]['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Connector'),
        '#options' => $this->getAllConnectorOptions(),
        '#default_value' => (
          !empty($connector_id) ?
          $connector_id :
          NULL
        ),
        '#ajax' => [
          'callback' => [$this, 'ajaxChangeConnector'],
          'wrapper' => 'connectors-list',
        ],
      ];

      // Load form fields.
      if (!empty($connector_id)) {
        $connector_objs = ContactFormConnector::loadMultiple([
          $connector_id,
        ]);
        if (!empty($connector_objs) && !empty($connector_objs[$connector_id])) {
          $connector_obj = $connector_objs[$connector_id];
          $form['connectors'][$key] += $connector_obj
            ->getConnector($connector_config)
            ->extraSettingsForm($connector_config);
        }
      }

      $form['connectors'][$key]['remove_connector'] = [
        '#type' => 'button',
        '#value' => $this->t('Remove'),
        '#name' => '__remove_connector__' . $key,
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [$this, 'ajaxRemoveConnector'],
          'wrapper' => 'connectors-list',
        ],
      ];
    }

    $form['add_connector'] = [
      '#type' => 'button',
      '#value' => $this->t('Add connector'),
      '#name' => '__add_connector__',
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, 'ajaxAddConnector'],
        'wrapper' => 'connectors-list',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback: Add connector button.
   */
  public function ajaxAddConnector($form, &$form_state) {
    return NestedArray::getValue($form, ['connectors']);
  }

  /**
   * AJAX callback: Connector change dropdown.
   */
  public function ajaxChangeConnector($form, &$form_state) {
    return NestedArray::getValue($form, ['connectors']);
  }

  /**
   * AJAX callback: Remove connector button.
   */
  public function ajaxRemoveConnector($form, &$form_state) {
    return NestedArray::getValue($form, ['connectors']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('contact_form_connect.settings');
    $build_info = $form_state->getBuildInfo();
    $form_id = $build_info['args'][0]['contactFormEntity']->id();

    $tree = $form_state->getValue(['connectors']);
    $config->set($form_id . '.contact_form_connectors', $tree);
    $config->save();
    Cache::invalidateTags(['config:core.entity_form_display.contact_message.' . $form_id . '.default']);
    parent::submitForm($form, $form_state);
  }

}
