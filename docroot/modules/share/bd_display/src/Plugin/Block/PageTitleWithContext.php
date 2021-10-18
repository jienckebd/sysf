<?php

namespace Drupal\bd_display\Plugin\Block;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block to display the page title.
 *
 * @Block(
 *   id = "page_title_with_context_block",
 *   admin_label = @Translation("Page title with context"),
 *   forms = {
 *     "settings_tray" = FALSE,
 *   },
 * )
 */
class PageTitleWithContext extends BlockBase implements TitleBlockPluginInterface {

  /**
   * The page title: a string (plain title) or a render array (formatted title).
   *
   * @var string|array
   */
  protected $title = '';

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'use_page_title' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;
    $form['use_page_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use page title'),
      '#default_value' => $config['use_page_title'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['use_page_title'] = $form_state->getValue('use_page_title');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->configuration;
    $title = $this->title;

    if ($config['use_page_title']) {
      $request = \Drupal::request();
      if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $title = \Drupal::service('title_resolver')->getTitle($request, $route);
      }
    }

    return [
      '#type' => 'page_title',
      '#title' => $title,
    ];
  }

}
