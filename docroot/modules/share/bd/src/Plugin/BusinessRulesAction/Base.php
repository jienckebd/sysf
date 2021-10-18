<?php

namespace Drupal\bd\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for business rules action plugins.
 */
abstract class Base extends BusinessRulesActionPlugin {
  use StringTranslationTrait;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityHelper = \Drupal::service('entity.helper');
    $this->logger = \Drupal::logger('theme');
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings = [];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    $result = [];
    return $result;
  }

}
