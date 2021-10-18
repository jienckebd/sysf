<?php

namespace Drupal\bd\Config;

/**
 *
 */
trait ConfigTrait {

  /**
   * @param array $data
   *
   * @return \Drupal\bd\Config\Config
   */
  public static function fromArray(array $data) {
    return new static($data);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      $this->data[] = $value;
    }
    else {
      $this->data[$offset] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    return isset($this->data[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    unset($this->data[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    return isset($this->data[$offset]) ? $this->data[$offset] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    return current($this->data);
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return key($this->data);
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    return next($this->data);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    return reset($this->data);
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    return key($this->data) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function resetOverriddenData() {
    unset($this->overriddenData);

    $remove_keys = [
      '_core',
    ];

    foreach ($remove_keys as $key) {
      if (isset($this->data[$key])) {
        unset($this->data[$key]);
      }
    }

    return $this;
  }

}
