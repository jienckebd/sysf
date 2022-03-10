<?php

namespace Drupal\design_system\Plugin\Condition;

use Drupal\bd\Component\Arrays\NestedArray;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\rules\Core\RulesConditionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rules plugin.
 *
 * @Condition(
 *   id = "form_state_data",
 *   label = @Translation("Form submission data"),
 *   category = @Translation("Forms"),
 *   context_definitions = {
 *     "operator" = @ContextDefinition("string",
 *       label = @Translation("Operator"),
 *       description = @Translation("The comparison operator from: ==, >, <, >=, <="),
 *       assignment_restriction = "input",
 *       default_value = "==",
 *       required = FALSE
 *     ),
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Type"),
 *       description = @Translation("The type of data to get from form submission data, either values or storage."),
 *       default_value = "values"
 *     ),
 *     "key" = @ContextDefinition("any",
 *       label = @Translation("Key"),
 *       description = @Translation("The dot annotated key."),
 *     ),
 *     "value" = @ContextDefinition("any",
 *       label = @Translation("Value"),
 *       description = @Translation("The value."),
 *     ),
 *   }
 * )
 */
class FormStateData extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The rules logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * FormStateData constructor.
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
   * @param string $operator
   * @param $type
   * @param $key
   * @param $value
   * @return bool|void
   */
  protected function doEvaluate(string $operator, $type, $key, $value) {

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

    $return = FALSE;
    if ($operator == '==') {
      $return = ($form_state_data_value == $value);
    }
    elseif ($operator == '>') {
      $return = ($form_state_data_value > $value);
    }
    elseif ($operator == '<') {
      $return = ($form_state_data_value < $value);
    }
    elseif ($operator == '>=') {
      $return = ($form_state_data_value >= $value);
    }
    elseif ($operator == '<=') {
      $return = ($form_state_data_value <= $value);
    }
    else {
      $this->logger->warning("Invalid operator: @operator", [
        '@operator' => $operator,
      ]);
    }

    return $return;
  }

}
