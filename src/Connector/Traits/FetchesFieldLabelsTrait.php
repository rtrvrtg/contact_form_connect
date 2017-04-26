<?php
/**
 * @file
 * Trait that extracts field labels from a content message.
 */

namespace Drupal\contact_form_connect\Connector\Traits;

use Drupal\field\Field;
use Drupal\contact\Entity\Message;

/**
 * Trait that extracts field labels from a content message.
 */
trait FetchesFieldLabelsTrait {
  /**
   * Get list of all field labels.
   */
  protected function fieldLabels(Message $contact_message) {
    $labels = [];
    $form_id = $contact_message->contact_form->getValue()[0]['target_id'];
    $bundle_fields = \Drupal::entityManager()->getFieldDefinitions('contact_message', $form_id);

    foreach ($bundle_fields as $field_name => $def) {
      $label = $def->getLabel();
      // If it's a string, use it verbatim.
      if (is_string($label)) {
        $labels[$field_name] = $label;
      }
      // If it walks like something that can render a string...
      // ...well, you know.
      elseif (method_exists($label, '__toString')) {
        $labels[$field_name] = $label->__toString();
      }
      // Otherwise, try and fudge a string out of it.
      else {
        $labels[$field_name] = '' . $label;
      }
    }
    return $labels;
  }

}
