<?php

namespace Drupal\design_system\Plugin\ArrayProcessor;

use Drupal\Core\Render\Element;

/**
 * Attach common layout, region, and component attributes.
 *
 * @ArrayProcessor(
 *   plugin_type = "array_processor",
 *   id = "layout_elements",
 *   label = @Translation("Layout elements"),
 *   description = @Translation("Attach common layout, region, and component attributes.")
 * )
 */
class LayoutElements extends Base {

  /**
   * @param array $build
   * @param array $context
   */
  public function process(array &$build, array &$context) {
    $this->recurseAttachLayoutAttributes($build, $context);
  }

  /**
   * @param array $build
   * @param array $context
   * @param bool $is_root_layout
   * @param bool $is_root_region
   */
  protected function recurseAttachLayoutAttributes(array &$build, array &$context = [], $is_root_layout = TRUE, $is_root_region = TRUE) {

    if (!isset($context['is_root'])) {
      $context['is_root'] = TRUE;
      $context['layout_delta'] = 0;
    }

    foreach (Element::children($build) as $child_key) {

      $child = &$build[$child_key];
      if (!is_array($child)) {
        continue;
      }

      if (!empty($child['#component'])) {

        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity_component */
        $entity_component = $child['#component'];

        $component_type = $entity_component->bundle();
        $is_layout_component = $entity_component->hasField('field_cmp_region');

        switch ($component_type) {

          case 'layout':
            $child['#attributes']['class'][] = 'layout';

            if (!empty($entity_component->layoutBuilderConfig)) {
              $layout_delta = $entity_component->layoutBuilderConfig['layout_delta'];
              $context['parent_layout_delta'] = $layout_delta;
            }
            else {
              $layout_delta = 'todo';
            }

            $child['#layout_delta'] = $layout_delta;
            $child['#is_root_layout'] = $is_root_layout;
            $child['#attributes']['data-layout-delta'] = $layout_delta;
            $is_root_layout = FALSE;

            break;

          case 'region':
            $child['#attributes']['class'][] = 'region';

            if (!empty($entity_component->layoutBuilderConfig)) {
              $layout_delta = $entity_component->layoutBuilderConfig['layout_delta'];
              $region_name = $entity_component->layoutBuilderConfig['region_name'];
              $context['parent_layout_delta'] = $layout_delta;
              $context['parent_region_name'] = $region_name;
            }
            else {
              $parent_layout_delta = $context['parent_layout_delta'];
              $parent_region_name = $context['parent_region_name'];
              $parent_component_uuid = $context['parent_component_uuid'];

              if (!isset($context['sublayout'][$parent_component_uuid]['count'])) {
                $context['sublayout'][$parent_component_uuid]['count'] = 0;
              }

              $context['sublayout'][$parent_component_uuid]['count']++;
              $sublayout_region_name = "row1_col{$context['sublayout'][$parent_component_uuid]['count']}";

              $layout_delta = $parent_layout_delta;
              $region_name = "{$parent_region_name}__{$parent_component_uuid}__{$sublayout_region_name}";
            }

            $child['#attributes']['data-region-layout-delta'] = $layout_delta;
            $child['#attributes']['data-region'] = $region_name;
            $child['#layout_delta'] = $layout_delta;
            $child['#region_name'] = $region_name;
            $child['#is_root_region'] = $is_root_region;
            $is_root_region = FALSE;

            break;

          default:
            $child['#attributes']['class'][] = 'component';

            if (!empty($entity_component->layoutBuilderConfig)) {
              $layout_delta = $entity_component->layoutBuilderConfig['layout_delta'];
              $region_name = $entity_component->layoutBuilderConfig['region_name'];
              $component_uuid = $entity_component->layoutBuilderConfig['component_uuid'];
              $context['parent_layout_delta'] = $layout_delta;
              $context['parent_region_name'] = $region_name;
              $context['parent_component_uuid'] = $component_uuid;
              $is_root_component = TRUE;
            }
            else {
              $parent_layout_delta = $context['parent_layout_delta'];
              $parent_region = $context['parent_region_name'];
              $layout_delta = $parent_layout_delta;
              $region_name = $parent_region;
              $component_uuid = 0;
              $is_root_component = FALSE;
            }
            $child['#attributes']['data-component-layout-delta'] = $layout_delta;
            $child['#attributes']['data-component-region'] = $region_name;
            $child['#attributes']['data-layout-block-uuid'] = $component_uuid;
            $child['#layout_delta'] = $layout_delta;
            $child['#region_name'] = $region_name;
            $child['#component_uuid'] = $component_uuid;
            $child['#is_root_component'] = $is_root_component;
            break;

        }

      }

      $this->recurseAttachLayoutAttributes($child, $context, $is_root_layout, $is_root_region);

    }

  }

}
