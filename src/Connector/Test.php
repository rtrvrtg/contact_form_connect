<?php

namespace Drupal\contact_form_connect\Connector;

use Drupal\contact_form_connect\Connector\Traits\TranslatesSpreadsheetColumnsTrait;
use Drupal\contact_form_connect\Connector\Traits\ReorganisesTableFieldsTrait;

class Test {
  use TranslatesSpreadsheetColumnsTrait;
  use ReorganisesTableFieldsTrait;

  protected $verbose = TRUE;

  public function testTableAddRow() {
    $out = $this->computeUpdate(
      [
        ['bar', 'foo'],
        ['a', 'b'],
        ['c', 'd'],
      ],
      [
        'bar' => '2',
        'foo' => '1',
      ]
    );
    print_r($out);
  }

  public function testTableAddRowAndReorganiseRow() {
    $out = $this->computeUpdate(
      [
        ['bar', 'foo'],
        ['a', 'b'],
        ['c', 'd'],
      ],
      [
        'foo' => '1',
        'bar' => '2',
        'blat' => '3',
      ]
    );
    print_r($out);
  }
}
