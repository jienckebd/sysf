<?php

namespace Drupal\bd\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Get value from an entity field value of a specific context.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "entity_values",
 *   title = @Translation("Entity Values")
 * )
 */
class EntityValues extends ArgumentDefaultPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['property'] = ['default' => 'value'];
    $options['context_id'] = ['default' => ''];
    $options['field_name'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $entity_type_id = $this->argument->getEntityType();

    $form['context_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Context'),
      '#description' => $this->t('Select a context.'),
      '#required' => TRUE,
      '#default_value' => $this->options['context_id'],
      '#options_provider' => [
        'plugin_id' => 'context',
      ],
    ];
    $form['field_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Field Name'),
      '#description' => $this->t('The field name.'),
      '#required' => TRUE,
      '#default_value' => $this->options['field_name'],
      '#options_provider' => [
        'plugin_id' => 'entity_field',
        'plugin_config' => [
          'entity_type' => $entity_type_id,
        ],
      ],
    ];
    $form['property'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Property'),
      '#description' => $this->t('The property of the entity field value to check such as value, target_id, etc.'),
      '#required' => TRUE,
      '#default_value' => $this->options['property'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {

    /** @var \Drupal\Core\Plugin\Context\LazyContextRepository $context_repository */
    $context_repository = \Drupal::service('context.repository');

    $context_id = $this->options['context_id'];
    $field_name = $this->options['field_name'];
    $property = $this->options['property'];

    $return = [];

    if (!$context_result = $context_repository->getRuntimeContexts([$context_id])) {
      return $return;
    }

    /** @var \Drupal\Core\Plugin\Context\ContextInterface $context */
    $context = reset($context_result);

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $context->getContextValue();

    if ($entity->hasField($field_name)) {
      $field_values = $entity->get($field_name)->getValue();
      foreach ($field_values as $key => $data) {
        if (!empty($data[$property])) {
          $return[] = $data[$property];
        }
      }
    }

    return implode(',', $return);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }

}
