<?php

namespace Drupal\bd\Plugin\DataType;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * The option data type.
 *
 * @DataType(
 *   id = "option",
 *   label = @Translation("Option")
 * )
 */
class Option extends StringData implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return array_keys($this->getPossibleOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return array_keys($this->getSettableOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    return $this->getPossibleOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    $option = [];
    return $option;
  }

}
