<?php

namespace Drupal\content_moderation_scheduled_updates\Plugin\Field\FieldWidget;

use Drupal\content_moderation\ModerationInformation;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\scheduled_updates\ScheduledUpdateInterface;
use Drupal\workflows\TransitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Moderation state via inline entity form.
 *
 * @FieldWidget(
 *   id = "cmsu_moderation_state",
 *   label = @Translation("Moderation state (Scheduled Update)"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class CmsuModerationStateWidget extends OptionsSelectWidget implements ContainerFactoryPluginInterface {

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformation
   */
  protected $moderationInformation;

  /**
   * Constructs a new CmsuModerationStateWidget object.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   Field settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user service.
   * @param \Drupal\content_moderation\ModerationInformation $moderationInformation
   *   Moderation information service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountInterface $currentUser, ModerationInformation $moderationInformation) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->currentUser = $currentUser;
    $this->moderationInformation = $moderationInformation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    try {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $parentEntity */
      $parentEntity = $this->getParentEntity($form, $form_state);
    }
    catch (\InvalidArgumentException $e) {
      // The parent entity cannot be determined if the user is viewing the
      // scheduled update entity edit route.
      return [];
    }

    $workflow = $this->moderationInformation->getWorkflowForEntity($parentEntity);

    // All transitions must be shown as there may be other scheduled updates.
    // Inspired by \Drupal\content_moderation\StateTransitionValidation::getValidTransitions.
    $transitions = $workflow->getTypePlugin()->getTransitions();
    $transitions = array_filter($transitions, function (TransitionInterface $transition) use ($workflow) {
      // User must have permission to access the transitions.
      return $this->currentUser->hasPermission('use ' . $workflow->id() . ' transition ' . $transition->id());
    });

    $toStates = [];
    foreach ($transitions as $transition) {
      $toState = $transition->to();
      $toStates[$toState->id()] = $toState->label();
    }

    $element += [
      '#type' => 'select',
      '#options' => $toStates,
      '#default_value' => $items[$delta]->value ?? NULL,
      '#access' => count($toStates) > 0,
      '#normalize' => TRUE,
    ];

    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    return 'scheduled_update' === $field_definition->getTargetEntityTypeId()
      && $field_definition->getFieldStorageDefinition()->getCardinality() === 1;
  }

  /**
   * Get the entity which contains the inline entity form containing this field.
   *
   * @param array $form
   *   A form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A form state.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The parent entity.
   *
   * @throws \InvalidArgumentException
   *   Throws exception when parent entity not found.
   */
  public function getParentEntity(array $form, FormStateInterface $form_state): EntityInterface {
    $formObject = $form_state->getFormObject();
    if (!$formObject instanceof EntityFormInterface) {
      throw new \InvalidArgumentException('Form is not an entity form.');
    }

    $entity = $formObject->getEntity();
    if ($entity instanceof ScheduledUpdateInterface) {
      throw new \InvalidArgumentException('Parent entity cannot be a scheduled update entity.');
    }

    return $entity;
  }

}
