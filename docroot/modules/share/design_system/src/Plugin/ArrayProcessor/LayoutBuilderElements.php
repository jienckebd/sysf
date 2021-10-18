<?php

namespace Drupal\design_system\Plugin\ArrayProcessor;

use Drupal\Core\Render\Element;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Attach common layout, region, and component attributes for layout builder.
 *
 * @ArrayProcessor(
 *   plugin_type = "array_processor",
 *   id = "layout_builder_elements",
 *   label = @Translation("Layout builder elements"),
 *   description = @Translation("Attach common layout, region, and component attributes for layout builder."),
 *   required_contexts = {"section_storage"}
 * )
 */
class LayoutBuilderElements extends Base {

  use AjaxHelperTrait;
  use LayoutBuilderContextTrait;
  use LayoutBuilderHighlightTrait;

  /**
   * @param array $build
   * @param array $context
   */
  public function process(array &$build, array &$context) {

    /** @var \Drupal\layout_builder\SectionStorageInterface $section_storage */
    $section_storage = $context['section_storage'];
    $this->recurseProcessBuild($build, $section_storage);

  }

  /**
   * Recursively attach layout builder elements and attributes.
   *
   * @param array $build
   */
  protected function recurseProcessBuild(array &$build, SectionStorageInterface $section_storage) {

    foreach (Element::children($build) as $child_key) {

      $child = &$build[$child_key];
      if (!is_array($child)) {
        continue;
      }

      if (!empty($child['#component'])) {

        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity_component */
        $entity_component = $child['#component'];
        $component_type = $entity_component->bundle();

        switch ($component_type) {

          case 'layout':
            $this->attachLayoutElements($child, $entity_component, $section_storage);
            break;

          case 'region':
            $this->attachRegionElements($child, $entity_component, $section_storage);
            break;

          default:
            $this->attachBlockElements($child, $entity_component, $section_storage);
            break;

        }

      }

      $this->recurseProcessBuild($child, $section_storage);

    }

  }

  /**
   * Builds a link to add a new section at a given delta.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section to splice.
   *
   * @return array
   *   A render array for a link.
   */
  protected function buildAddSectionLink(SectionStorageInterface $section_storage, $delta, $weight = 1000) {
    $storage_type = $section_storage->getStorageType();
    $storage_id = $section_storage->getStorageId();

    $row_id = $delta + 1;

    // If the delta and the count are the same, it is either the end of the
    // layout or an empty layout.
    if ($delta === count($section_storage)) {
      if ($delta === 0) {
        $button_label = $this->t('Row below');
        $title_attribute = $this->t('Add a new row below this row.');
      }
      else {
        $button_label = $this->t('Row at bottom');
        $title_attribute = $this->t('Add a new row to the bottom of this layout.');
      }
    }
    // If the delta and the count are different, it is either the beginning of
    // the layout or in between two sections.
    else {
      if ($delta === 0) {
        $button_label = $this->t('Row at top');
        $title_attribute = $this->t('Add a new row to the top of this layout.');
      }
      else {
        $button_label = $this->t('Row between @first and @second', ['@first' => $row_id, '@second' => $row_id + 1]);
        $title_attribute = $this->t('Add a new row between rows @first and @second.', ['@first' => $row_id, '@second' => $row_id + 1]);
      }
    }

    $build_link = [
      '#type' => 'link',
      '#button_type' => 'primary',
      '#button_size' => 'sm',
      '#icon' => 'plus',
      '#title' => $button_label,
      '#url' => Url::fromRoute('layout_builder.choose_section',
        [
          'section_storage_type' => $storage_type,
          'section_storage' => $storage_id,
          'delta' => $delta,
        ],
        [
          'attributes' => [
            'class' => [
              'float-right',
              'use-ajax',
              'layout-builder__link',
              'layout-builder__link--add',
              'settings-tray-edit-mode-enable',
            ],
            'data-dialog-type' => 'dialog',
            'data-dialog-renderer' => 'off_canvas',
            'data-dialog-target' => 'layout-builder-sidebar',
            'data-ajax-throbber' => 'slider',
            'title' => $title_attribute,
            'data-tooltip' => 'bottom',
          ],
        ]
      ),
    ];

    return $build_link;
  }

  /**
   * @param array $build_layout
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_component
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   */
  protected function attachLayoutElements(array &$build_layout, ContentEntityInterface $entity_component, SectionStorageInterface $section_storage) {

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity_component */
    $entity_component = $build_layout['#component'];

    $delta = $build_layout['#layout_delta'];
    $is_layout_builder_row = $build_layout['#is_root_layout'];

    $storage_type = $section_storage->getStorageType();
    $storage_id = $section_storage->getStorageId();
    $row_id = $delta + 1;

    $section_label = $this->t('Row @layout_delta', [
      '@layout_delta' => $row_id,
    ]);

    $build_layout['#attributes']['data-layout-update-url'] = Url::fromRoute('layout_builder.move_block', [
      'section_storage_type' => $storage_type,
      'section_storage' => $storage_id,
    ])->toString();

    if ($is_layout_builder_row) {
      $build_layout['#attributes']['class'][] = 'layout-builder__layout';
      $build_layout['#attributes']['class'][] = 'layout-builder__section';
      $build_layout['#attributes']['data-layout-builder-highlight-id'] = $this->sectionUpdateHighlightId($delta);

      $build_layout['actions'] = [
        '#type' => 'actions',
        '#weight' => -1000,
        '#attributes' => [
          'class' => [
            'layout-builder--section--actions',
            'form-actions',
            'p-1',
            'w-100',
            'position-absolute',
          ],
        ],
      ];

      $build_layout['actions']['configure'] = [
        '#type' => 'link',
        '#button_type' => 'primary',
        '#button_size' => 'sm',
        '#icon' => 'cog',
        '#icon_position' => 'only',
        '#title' => $this->t('Configure @section', ['@section' => $section_label]),
        '#url' => Url::fromRoute('layout_builder.configure_section', [
          'section_storage_type' => $storage_type,
          'section_storage' => $storage_id,
          'delta' => $delta,
        ]),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'layout-builder__link',
            'layout-builder__link--configure',
            'settings-tray-edit-mode-enable',
          ],
          'data-dialog-type' => 'dialog',
          'data-dialog-size' => 'fw',
          'data-ajax-throbber' => 'slider',
          'data-dialog-class' => 'layout-builder-modal',
          'data-dialog-id' => 'layout-builder-component-configure',
          'data-tooltip' => 'bottom',
        ],
      ];
      $build_layout['actions']['configure']['#attributes']['title'] = $build_layout['actions']['configure']['#title'];

      $build_layout['actions']['remove'] = [
        '#type' => 'link',
        '#button_type' => 'danger',
        '#button_size' => 'sm',
        '#icon' => 'times-circle',
        '#icon_position' => 'only',
        '#title' => $this->t('Remove @section', ['@section' => $section_label]),
        '#url' => Url::fromRoute('layout_builder.remove_section', [
          'section_storage_type' => $storage_type,
          'section_storage' => $storage_id,
          'delta' => $delta,
        ]),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'layout-builder__link',
            'layout-builder__link--remove',
            'settings-tray-edit-mode-enable',
          ],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
          'data-dialog-class' => 'layout-builder-modal',
          'data-ajax-throbber' => 'slider',
          'data-tooltip' => 'bottom',
        ],
      ];
      $build_layout['actions']['remove']['#attributes']['title'] = $build_layout['actions']['remove']['#title'];

      $build_layout['actions']['section_label'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $section_label,
        '#attributes' => [
          'class' => [
            'layout-builder__section-label',
            'font-weight-bold',
            'small',
            'btn',
            'btn-darker',
            'btn-sm',
          ],
        ],
      ];

      $build_layout['actions']['add_row'] = $this->buildAddSectionLink($section_storage, $delta);
    }
    else {
      $d = 1;
    }

  }

  /**
   * @param array $build_region
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_component
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   */
  protected function attachRegionElements(array &$build_region, ContentEntityInterface $entity_component, SectionStorageInterface $section_storage) {

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity_component */
    $entity_component = $build_region['#component'];

    $is_layout_builder_region = $build_region['#is_root_region'];
    $delta = $build_region['#layout_delta'];
    $region = $build_region['#region_name'];

    $storage_type = $section_storage->getStorageType();
    $storage_id = $section_storage->getStorageId();

    // If in a nested region, $mock_region_id is the nested region ID.
    if (!empty($mock_region_id)) {
      $region_use = $mock_region_id;
    }
    else {
      $region_use = $region;
    }

    $section_id = $delta + 1;
    $section_label = $this->t('Section @section_id', [
      '@section_id' => $section_id,
    ]);
    $region_label = $this->t('Region @region_id', [
      '@region_id' => $region_use,
    ]);

    $build_region['layout_builder_add_block'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'layout-builder__add-block',
        ],
        'data-layout-builder-highlight-id' => $this->blockAddHighlightId($delta, $region_use),
      ],
      '#weight' => 1000,
    ];

    $build_region['layout_builder_add_block']['link'] = [
      '#type' => 'link',
      // Add one to the current delta since it is zero-indexed.
      '#title' => $this->t('Component'),
      '#button_type' => 'secondary',
      '#button_size' => 'sm',
      '#icon' => 'plus',
      '#icon_position' => 'before',
      '#url' => Url::fromRoute('layout_builder.choose_block',
        [
          'section_storage_type' => $storage_type,
          'section_storage' => $storage_id,
          'delta' => $delta,
          'region' => $region_use,
        ],
        [
          'attributes' => [
            'title' => $this->t('Add component'),
            'data-tooltip' => 'bottom',
            'class' => [
              'use-ajax',
              'layout-builder__link',
              'layout-builder__link--add',
              'settings-tray-edit-mode-enable',
            ],
            'data-dialog-type' => 'dialog',
            'data-dialog-renderer' => 'off_canvas',
            'data-ajax-throbber' => 'slider',
            'data-dialog-target' => 'layout-builder-sidebar',
          ],
        ]
      ),
    ];

    if (!$is_layout_builder_region) {

    }

    $build_region['#attributes']['class'][] = 'position-relative';
    $build_region['#attributes']['class'][] = 'layout-builder__region';
    $build_region['#attributes']['class'][] = 'js-layout-builder-region';
    $build_region['#attributes']['role'] = 'group';
    $build_region['#attributes']['aria-label'] = $this->t('@region region in @section', [
      '@region' => $region_label,
      '@section' => $section_label,
    ]);

    // Get weights of all children for use by the region label.
    // The region label is made visible when the move block dialog is open.
    $build[$region]['region_label'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['layout__region-info', 'layout-builder__region-label'],
        // A more detailed version of this information is already read by
        // screen readers, so this label can be hidden from them.
        'aria-hidden' => TRUE,
      ],
      '#markup' => $this->t('@region', ['@region' => $region_label]),
      // Ensures the region label is displayed first.
      '#weight' => -1000,
    ];

  }

  /**
   * @param array $build
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_component
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   */
  protected function attachBlockElements(array &$build, ContentEntityInterface $entity_component, SectionStorageInterface $section_storage) {

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity_component */
    $entity_component = $build['#component'];

    $is_root_component = $build['#is_root_component'];
    $delta = $build['#layout_delta'];
    $region = $build['#region_name'];
    $uuid = $build['#component_uuid'];

    $storage_type = $section_storage->getStorageType();
    $storage_id = $section_storage->getStorageId();

    $build['#attributes']['title'] = $this->t('@entity_label @entity_id (@entity_bundle)', [
      '@entity_label' => $entity_component->label(),
      '@entity_id' => $entity_component->id(),
      '@entity_bundle' => $entity_component->bundle(),
    ]);
    $build['#attributes']['data-tooltip'] = 'top';

    $build['#attributes']['class'][] = 'js-layout-builder-block';
    $build['#attributes']['class'][] = 'layout-builder-block';
    $build['#attributes']['data-layout-block-uuid'] = $uuid;
    $build['#attributes']['data-layout-builder-highlight-id'] = $this->blockUpdateHighlightId($uuid);
    $build['#contextual_links'] = [
      'layout_builder_block' => [
        'route_parameters' => [
          'section_storage_type' => $storage_type,
          'section_storage' => $storage_id,
          'delta' => $delta,
          'region' => $region,
          'uuid' => $uuid,
        ],
        // Add metadata about the current operations available in
        // contextual links. This will invalidate the client-side cache of
        // links that were cached before the 'move' link was added.
        // @see layout_builder.links.contextual.yml
        'metadata' => [
          'operations' => 'move:update:remove',
        ],
      ],
    ];
  }

}
