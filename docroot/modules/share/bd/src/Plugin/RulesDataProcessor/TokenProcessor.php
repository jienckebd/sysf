<?php

namespace Drupal\bd\Plugin\RulesDataProcessor;

use Drupal\rules\Plugin\RulesDataProcessor\TokenProcessor as Base;
use Drupal\rules\Context\ExecutionStateInterface;
use Drupal\typed_data\PlaceholderResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A data processor for placeholder token replacements.
 *
 * @RulesDataProcessor(
 *   id = "rules_tokens",
 *   label = @Translation("Placeholder token replacements")
 * )
 */
class TokenProcessor extends Base {

  /**
   * The placeholder resolver.
   *
   * @var \Drupal\typed_data\PlaceholderResolverInterface
   */
  protected $placeholderResolver;

  /**
   * Constructs a TokenProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\typed_data\PlaceholderResolverInterface $placeholder_resolver
   *   The placeholder resolver.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PlaceholderResolverInterface $placeholder_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $placeholder_resolver);
    $this->placeholderResolver = $placeholder_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('typed_data.placeholder_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($value, ExecutionStateInterface $rules_state) {

    $context = [];

    $token = \Drupal::token();
    $tokens_in_value = $token->scan($value);

    if (empty($tokens_in_value)) {
      return $value;
    }

    foreach ($tokens_in_value as $token_type_id => $token_type_tokens) {

      if ($rules_state->hasVariable($token_type_id)) {

        $rules_variable_state = $rules_state->getVariable($token_type_id);
        $context[$token_type_id] = $rules_variable_state->getValue();

      }

    }

    $options_token = [];
    $options_token['clear'] = TRUE;

    $processed_value = \Drupal::token()->replace($value, $context, $options_token);

    return $processed_value;
  }

}
