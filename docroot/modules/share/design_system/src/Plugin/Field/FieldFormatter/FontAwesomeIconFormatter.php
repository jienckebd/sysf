<?php

namespace Drupal\design_system\Plugin\Field\FieldFormatter;

use Drupal\fontawesome\Plugin\Field\FieldFormatter\FontAwesomeIconFormatter as Base;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Extends font awesome icon formatter.
 */
class FontAwesomeIconFormatter extends Base {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => '',
      'color' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $build = parent::settingsForm($form, $form_state);

    $build['size'] = [
      '#type' => 'select',
      '#normalize' => TRUE,
      '#title' => $this->t('Size'),
      '#description' => $this->t('This increases icon sizes relative to their container.'),
      '#options' => [
        '' => $this->t('Default'),
        'fa-xs' => $this->t('Extra Small'),
        'fa-sm' => $this->t('Small'),
        'fa-lg' => $this->t('Large'),
        'fa-2x' => $this->t('2x'),
        'fa-3x' => $this->t('3x'),
        'fa-4x' => $this->t('4x'),
        'fa-5x' => $this->t('5x'),
        'fa-6x' => $this->t('6x'),
        'fa-7x' => $this->t('7x'),
        'fa-8x' => $this->t('8x'),
        'fa-9x' => $this->t('9x'),
        'fa-10x' => $this->t('10x'),
      ],
      '#default_value' => $this->getSetting('size'),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Early opt-out if the field is empty.
    if (count($items) <= 0) {
      return [];
    }

    // Load the configuration settings.
    $configurationSettings = $this->configFactory->get('fontawesome.settings');

    // Attach the libraries as needed.
    $fontawesomeLibraries = [];
    if ($configurationSettings->get('method') == 'webfonts') {
      // Webfonts method.
      $fontawesomeLibraries[] = 'fontawesome/fontawesome.webfonts';

      // Attach the shim file if needed.
      if ($configurationSettings->get('use_shim')) {
        $fontawesomeLibraries[] = 'fontawesome/fontawesome.webfonts.shim';
      }
    }
    else {
      // SVG method.
      $fontawesomeLibraries[] = 'fontawesome/fontawesome.svg';

      // Attach the shim file if needed.
      if ($configurationSettings->get('use_shim')) {
        $fontawesomeLibraries[] = 'fontawesome/fontawesome.svg.shim';
      }
    }

    // Loop over each icon and build data.
    $icons = [];
    foreach ($items as $item) {
      // Get the icon settings.
      $iconSettings = unserialize($item->get('settings')->getValue());
      $cssStyles = [];

      // Format mask.
      $iconMask = '';
      if (!empty($iconSettings['masking']['mask'])) {
        $iconMask = $iconSettings['masking']['style'] . ' fa-' . $iconSettings['masking']['mask'];
      }
      unset($iconSettings['masking']);

      // Format power transforms.
      $iconTransforms = [];
      $powerTransforms = $iconSettings['power_transforms'];
      foreach ($powerTransforms as $transform) {
        if (!empty($transform['type'])) {
          $iconTransforms[] = $transform['type'] . '-' . $transform['value'];
        }
      }
      unset($iconSettings['power_transforms']);

      // Move duotone settings into the render.
      if (isset($iconSettings['duotone'])) {
        // Handle swap opacity flag.
        if (!empty($iconSettings['duotone']['swap-opacity'])) {
          $iconSettings['swap-opacity'] = $iconSettings['duotone']['swap-opacity'];
        }
        // Handle custom CSS styles.
        if (!empty($iconSettings['duotone']['opacity']['primary'])) {
          $cssStyles[] = '--fa-primary-opacity: ' . $iconSettings['duotone']['opacity']['primary'] . ';';
        }
        if (!empty($iconSettings['duotone']['opacity']['secondary'])) {
          $cssStyles[] = '--fa-secondary-opacity: ' . $iconSettings['duotone']['opacity']['secondary'] . ';';
        }
        if (!empty($iconSettings['duotone']['color']['primary'])) {
          $cssStyles[] = '--fa-primary-color: ' . $iconSettings['duotone']['color']['primary'] . ';';
        }
        if (!empty($iconSettings['duotone']['color']['secondary'])) {
          $cssStyles[] = '--fa-secondary-color: ' . $iconSettings['duotone']['color']['secondary'] . ';';
        }

        unset($iconSettings['duotone']);
      }

      // Add additional CSS styles if needed.
      if (isset($iconSettings['additional_classes'])) {
        $cssStyles[] = $iconSettings['additional_classes'];
      }

      $style = $item->get('style')->getValue();

      if (!empty($iconSettings['icon_color'])) {
        $style .= " text-{$iconSettings['icon_color']}";
      }

      if (!empty($iconSettings['icon_class'])) {
        foreach ($iconSettings['icon_class'] as $class) {
          $style .= " {$class}";
        }
      }

      if (!empty($iconSettings['icon_class'])) {
        unset($iconSettings['icon_class']);
      }

      $icons[] = [
        '#theme' => 'fontawesomeicon',
        '#tag' => $configurationSettings->get('tag'),
        '#name' => 'fa-' . $item->get('icon_name')->getValue(),
        '#style' => $style,
        '#settings' => implode(' ', $iconSettings),
        '#transforms' => implode(' ', $iconTransforms),
        '#mask' => $iconMask,
        '#css' => implode(' ', $cssStyles),
      ];
    }

    // Get the icon settings.
    $settings = $this->getSettings();

    return [
      [
        '#theme' => 'fontawesomeicons',
        '#icons' => $icons,
        '#layers' => $settings['layers'],
      ],
      '#attached' => [
        'library' => $fontawesomeLibraries,
      ],
    ];
  }

}
