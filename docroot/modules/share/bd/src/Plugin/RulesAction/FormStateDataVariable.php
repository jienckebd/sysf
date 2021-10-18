<?php

namespace Drupal\bd\Plugin\RulesAction;

use Drupal\bd\Component\Arrays\NestedArray;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rules action.
 *
 * @RulesAction(
 *   id = "form_state_data_variable",
 *   label = @Translation("Set a variable from form submission data"),
 *   category = @Translation("Forms"),
 *   context_definitions = {
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Type"),
 *       description = @Translation("The type of data to get from form submission data, either values or storage."),
 *       default_value = "values"
 *     ),
 *     "key" = @ContextDefinition("any",
 *       label = @Translation("Key"),
 *       description = @Translation("The dot annotated key."),
 *     ),
 *   },
 *   provides = {
 *     "form_state_data" = @ContextDefinition("any",
 *       label = @Translation("Form state data")
 *     ),
 *   }
 * )
 */
class FormStateDataVariable extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The rules logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * FormStateDataVariable constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.rules')
    );
  }

  /**
   * @param $type
   * @param $key
   */
  protected function doExecute($type, $key) {

    /** @var \Drupal\Core\Form\FormStateInterface $form_state */
    $form_state = $_ENV['SYS_FORM_STATE'];

    if ($type == 'values') {
      $data = $form_state->getValues();
    }
    elseif ($type == 'storage') {
      $data = $form_state->getStorage();
    }
    else {
      $this->logger->warning("Invalid form submission data type: @type", [
        '@type' => $type,
      ]);
      return;
    }

    $parents = explode('.', $key);
    $form_state_data_value = NestedArray::getValue($data, $parents);

    $this->setProvidedValue('form_state_data', $form_state_data_value);
  }

}
