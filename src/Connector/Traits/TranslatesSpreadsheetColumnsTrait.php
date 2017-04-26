<?php
/**
 * @file
 * Trait that extracts field labels from a content message.
 */

namespace Drupal\contact_form_connect\Connector\Traits;

use Drupal\field\Field;
use Drupal\contact\Entity\Message;

/**
 * Trait that translates column indexes into spreadsheet columns.
 */
trait TranslatesSpreadsheetColumnsTrait {
  /**
   * Translate a column number to spreadsheet columns.
   *
   * @param int $column_number
   *   The column number, starting from 0.
   *
   * @return string
   *   The column letter, starting with A.
   */
  public function columnToLetter($column_number = 0) {
    $temp = $letter = '';
    $tmp_column_number = $column_number + 1;
    while ($tmp_column_number > 0) {
      $temp = ($tmp_column_number - 1) % 26;
      $letter = chr($temp + 65) . $letter;
      $tmp_column_number = floor(($tmp_column_number - $temp - 1) / 26);
    }
    return $letter;
  }

}
