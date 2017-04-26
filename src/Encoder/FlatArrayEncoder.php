<?php

namespace Drupal\contact_form_connect\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class FlatArrayEncoder implements EncoderInterface, DecoderInterface {
  protected $separator;
  protected $config;
  protected static $format = 'flat_array';

  /**
   * Constructs the class.
   */
  public function __construct($separator = ' -- ') {
    $this->separator = $separator;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return $format == static::$format;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return $format == static::$format;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    // Start by converting everything to an array.
    switch (gettype($data)) {
      case 'array':
        break;

      case 'object':
        $data = (array) $data;
        break;

      // May be bool, integer, double, string, resource, NULL, or unknown.
      // Cast as array.
      default:
        $data = [$data];
        break;
    }

    // Get the prefix if there is one.
    $prefix = (
      !empty($context['__flat_array_prefix']) ?
      $context['__flat_array_prefix'] . $this->separator :
      ''
    );

    // Now, generate the output.
    $output = [];
    foreach ($data as $k => $v) {
      if (
        is_array($context['skip_keys']) &&
        array_search($k . '', $context['skip_keys']) !== FALSE
      ) {
        continue;
      }

      $key = $k;
      if (!empty($context['remap_headers'][$k])) {
        $key = (
          method_exists($context['remap_headers'][$k], '__toString') ?
          $context['remap_headers'][$k]->__toString() :
          $context['remap_headers'][$k] . ''
        );
      }

      $heading = $prefix . $key;

      if (gettype($v) == 'array' || gettype($v) == 'object') {
        $encoded_val = $this->encode(
          $v,
          $format,
          array_merge($context, ['__flat_array_prefix' => $heading])
        );
        if (!empty($encoded_val)) {
          if (!!$context['reduce_single'] && count($encoded_val) == 1) {
            $output[$heading] = reset($encoded_val);
          }
          else {
            $output = array_merge($output, $encoded_val);
          }
        }
      }
      else {
        $output[$heading] = $v;
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = array()) {
    return NULL;
  }
}
