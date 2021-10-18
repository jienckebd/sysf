<?php

namespace Drupal\bd\Plugin\RulesAction;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes system messages.
 *
 * @RulesAction(
 *   id = "system_message_delete",
 *   label = @Translation("Delete system messages"),
 *   category = @Translation("System"),
 *   context_definitions = {
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Message type"),
 *       description = @Translation("The message type: status, warning, error, or legal."),
 *       required = FALSE
 *     ),
 *   }
 * )
 */
class SystemMessageDelete extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a SystemMessageDelete object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger')
    );
  }

  /**
   * @param $type
   */
  protected function doExecute($type) {

    if (!empty($type)) {
      $this->messenger->deleteByType($type);
    }
    else {
      $this->messenger->deleteAll();
    }

  }

}
