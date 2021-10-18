<?php

namespace Drupal\design_system\Entity\Entity;

/**
 * Provides logic to inject in entity form display and entity view display.
 */
trait EntityDisplayTrait {

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The config processor.
   *
   * @var \Drupal\bd\Config\ProcessorInterface
   */
  protected $configProcessor;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->designSystem = \Drupal::service('design.system');
    $this->configProcessor = \Drupal::service('config.processor');
  }

}
