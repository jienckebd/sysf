<?php

namespace Drupal\bd\Logger;

use Drupal\Core\Logger\LoggerChannelInterface as Base;
use Drupal\Core\Entity\EntityInterface;

/**
 * Extends core logger channel interface.
 */
interface LoggerChannelInterface extends Base {

  /**
   * Logs an exception with an arbitrary level.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param mixed $level
   * @param string $message
   * @param array $context
   *
   * @return void
   */
  public function logEntity(EntityInterface $entity, $level, $message, array $context = []);

  /**
   * Logs an exception with an arbitrary level.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $field_name
   * @param mixed $level
   * @param string $message
   * @param array $context
   *
   * @return void
   */
  public function logEntityField(EntityInterface $entity, $field_name, $level, $message, array $context = []);

  /**
   * Logs an exception with an arbitrary level.
   *
   * @param \Exception $e
   * @param mixed $level
   * @param string $message
   * @param array $context
   *
   * @return void
   */
  public function logException(\Exception $e, $level, $message, array $context = []);

}
