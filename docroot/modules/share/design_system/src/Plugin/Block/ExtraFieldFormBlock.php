<?php

namespace Drupal\design_system\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block that renders an extra field from an entity.
 *
 * This block handles fields that are provided by implementations of
 * hook_entity_extra_field_info().
 *
 * @see \Drupal\layout_builder\Plugin\Block\FieldBlock
 *   This block plugin handles all other field entities not provided by
 *   hook_entity_extra_field_info().
 *
 * @Block(
 *   id = "extra_field_form_block",
 *   deriver = "\Drupal\design_system\Plugin\Derivative\ExtraFieldFormDeriver",
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class ExtraFieldFormBlock extends ExtraFieldBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#tree' => TRUE,
      '#mock_field_widget_config' => $this->getConfiguration(),
      '#mock_field_widget_name' => $this->fieldName,
    ];
    $build['#process'][] = [$this, 'processFieldWidget'];

    // Throws warning in language_form_alter(). Will be adjusted in this process
    // callback.
    $build['#access'] = TRUE;
    return $build;
  }

  /**
   * Process callback used so form builder can attach parents.
   *
   * Parents are needed by widget subform state.
   *
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $complete_form
   *
   * @return array
   */
  public function processFieldWidget(array $element, FormStateInterface $form_state, array &$complete_form) {

    $element['#mock_field_widget_name'] = $this->fieldName;
    $element['#mock_field_widget_config'] = $this->getConfiguration();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewFallbackString() {
    $entity = $this->getEntity();
    $extra_fields = $this->entityFieldManager->getExtraFields($entity->getEntityTypeId(), $entity->bundle());
    return new TranslatableMarkup('"@field" field', ['@field' => $extra_fields['form'][$this->fieldName]['label']]);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return $this->getEntity()->access('edit', $account, TRUE);
  }

}
