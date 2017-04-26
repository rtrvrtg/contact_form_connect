<?php
/**
 * @file
 * Trait that reorganises spreadsheet fields.
 */

namespace Drupal\contact_form_connect\Connector\Traits;

/**
 * Trait that reorganises spreadsheet fields.
 */
trait ReorganisesTableFieldsTrait {
  /**
   * Compute updates.
   *
   * @param array $original_rows
   *   The original spreadsheet rows, as an array of arrays.
   * @param array $new_assoc_row
   *   The new row, in associative array form.
   *
   * @return array
   */
  protected function computeUpdate(array $original_rows = [], array $new_assoc_row = []) {
    $field_map = $this->computeFieldMap($original_rows);

    if ($this->verbose) {
      \Drupal::logger('contact_form_connect')->notice(
        'Original field map is ' .
        print_r($field_map, 1)
      );
    }

    // Now, add the new row.
    if (!empty($field_map)) {
      $headers = $original_headers = $original_rows[0];
      // Instead of tweaking every single row to be in the new order,
      // we should rearrange around any new fields that have appeared.
      // If there are any new items in header, append to headers.
      $new_assoc_headers = array_keys($new_assoc_row);
      $diff = array_diff($new_assoc_headers, $original_headers);
      if (!empty($diff)) {
        // Just append the new headers to the end of the existing ones.
        $headers = array_merge($headers, $diff);
      }

      // Loop through the headers and create the new row.
      $added_row = [];
      foreach ($headers as $index => $h) {
        if (!empty($new_assoc_row[$h])) {
          $added_row[$h] = $new_assoc_row[$h];
        }
        else {
          $added_row[$h] = '';
        }
      }
      $field_map[] = $added_row;
    }
    else {
      $field_map[] = $new_assoc_row;
    }

    if ($this->verbose) {
      \Drupal::logger('contact_form_connect')->notice(
        'New field map is ' .
        print_r($field_map, 1)
      );
    }

    // Get the updated rows,
    // and return the computed differences.
    $new_rows = $this->fieldMapToRows($field_map);

    if ($this->verbose) {
      \Drupal::logger('contact_form_connect')->notice(
        'New rows are ' .
        print_r($new_rows, 1)
      );
    }

    return $this->generateSpreadsheetChanges($original_rows, $new_rows);
  }

  /**
   * First, remove the header and build the field map.
   */
  protected function computeFieldMap(array $original_rows = []) {
    $field_map = [];
    if (count($original_rows) > 0) {
      // Pop the top row off so it becomes the header.
      $clone_rows = $original_rows;
      $original_header = array_shift($clone_rows);
      // Populate the field map, making sure to fill any gaps in.
      $field_map = array_map(function($r) use ($original_header) {
        $orig_row = [];
        foreach ($original_header as $index => $h) {
          $orig_row[$h] = (
            !empty($r[$index]) ?
            $r[$index] :
            ''
          );
        }
        return $orig_row;
      }, $clone_rows);
    }
    return $field_map;
  }

  /**
   * Convert back to CSV-style rows.
   */
  protected function fieldMapToRows(array $field_map = []) {
    $rows = [];
    $headers = [];
    foreach ($field_map as $row) {
      $row_headers = array_keys($row);
      if (count($row_headers) > count($headers)) {
        $headers = $row_headers;
      }
      $rows[] = array_values($row);
    }
    array_unshift($rows, $headers);
    return $rows;
  }

  /**
   * Generate the list of spreadsheet inserts and updates that are needed.
   *
   * @param array $old_rows
   *   The old spreadsheet rows.
   * @param array $new_rows
   *   The new spreadsheet rows.
   *
   * @return array
   *   An array with two keys:
   *   all updates under the key update,
   *   all inserts under the key insert.
   */
  protected function generateSpreadsheetChanges(array $old_rows = [], array $new_rows = []) {
    $changes = [
      'update' => [],
      'insert' => [],
    ];

    // There's not really any way that we can have
    // fewer rows in the new table.
    if (count($new_rows) > count($old_rows)) {
      for ($i = count($old_rows); $i < count($new_rows); $i++) {
        $changes['insert'][] = array_values($new_rows[$i]);
      }
    }

    // Now, we compute the difference in each row.
    for ($i = 0; $i < count($old_rows); $i++) {
      $new_row = $this->rTrimArray($new_rows[$i]);
      if ($new_row !== $old_rows[$i]) {
        $changes['update'][$i] = array_values($new_row);
      }
    }

    return $changes;
  }

  /**
   * Trim the array down to size.
   */
  protected function rTrimArray(array $arr = []) {
    $copy = $arr;
    for ($i = count($arr) - 1; $i >= 0; $i--) {
      if (!empty($copy[$i])) {
        break;
      }
      array_pop($copy);
    }
    return $copy;
  }

}
