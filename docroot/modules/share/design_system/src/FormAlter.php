<?php

namespace Drupal\design_system;

use Drupal\bd\Event\FormBuild;
use Drupal\bd\Event\FormSubmit;
use Drupal\bd\Event\FormValidate;
use Drupal\bd\Event\FormValidateFail;
use Drupal\bd\Event\FormValidatePass;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DrupalKernel;
use Drupal\layout_builder\Section;
use Drupal\views\Form\ViewsForm;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\design_system\Ajax\Traits\Form;

/**
 * Provides form alter logic.
 */
class FormAlter implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use Form;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The default cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a FormAlter object.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity storage for views.
   * @param \Drupal\design_system\DesignSystem $design_system
   *   The design system.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache back end.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityHelper $entity_helper,
    DesignSystem $design_system,
    RendererInterface $renderer,
    RouteMatchInterface $route_match,
    Connection $database,
    MessengerInterface $messenger,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->designSystem = $design_system;
    $this->renderer = $renderer;
    $this->routeMatch = $route_match;
    $this->database = $database;
    $this->messenger = $messenger;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.helper'),
      $container->get('design.system'),
      $container->get('renderer'),
      $container->get('current_route_match'),
      $container->get('database'),
      $container->get('messenger'),
      $container->get('cache.default'),
      $container->get('logger.channel.design_system')
    );
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form_id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $skip_form_id = [
      'view_edit_form',
      'view_preview_form',
      'view_duplicate_form',
    ];
    if (in_array($form_id, $skip_form_id)) {
      return;
    }

    $_ENV['SYS_FORM_STATE'] = $form_state;

    $event = new FormBuild($form_state->getFormObject()->getFormId(), $form, $form_state);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch(FormBuild::EVENT_NAME, $event);

    $form_object = $form_state->getFormObject();

    $base_form_id = $form_id;
    if (method_exists($form_object, 'getBaseFormId')) {
      $base_form_id = $form_object->getBaseFormId();
    }

    if (!empty($form['actions']) && empty($form['#actions']['#type'])) {
      $form['actions']['#type'] = 'actions';
    }

    if ($form_object instanceof ViewsForm) {
      $this->viewsFormAlter($form, $form_state, $form_id);
    }

    if ($form_id == 'views_exposed_form') {
      $this->viewsExposedFormAlter($form, $form_state, $form_id);
    }

    $form['#attached']['library'][] = 'core/drupal.states';
    $form['#process'][] = [static::class, 'processEntityForm'];
    $form['#after_build'][] = [static::class, 'afterBuildEntityForm'];
    $form['#validate'][] = [static::class, 'formValidate'];
    $form['#submit'][] = [static::class, 'formSubmit'];

    $form_specific_callback = "form__{$base_form_id}";
    if (method_exists($this, $form_specific_callback)) {
      $this->{$form_specific_callback}($form, $form_state);
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function formValidate(array $form, FormStateInterface $form_state) {

    // Set the account twice on the event: as the main subject but also in the
    // list of arguments.
    $form_id = $form_state->getFormObject()->getFormId();

    $_ENV['SYS_FORM_STATE'] = $form_state;

    $event = new FormValidate($form_id, $form, $form_state);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch(FormValidate::EVENT_NAME, $event);

    if ($form_state->getErrors()) {
      $event = new FormValidateFail($form_id, $form, $form_state);
      $event_dispatcher->dispatch(FormValidateFail::EVENT_NAME, $event);
    }
    else {
      $event = new FormValidatePass($form_id, $form, $form_state);
      $event_dispatcher->dispatch(FormValidatePass::EVENT_NAME, $event);
    }

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function formSubmit(array $form, FormStateInterface $form_state) {

    $_ENV['SYS_FORM_STATE'] = $form_state;

    $event = new FormSubmit($form_state->getFormObject()->getFormId(), $form, $form_state);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch(FormSubmit::EVENT_NAME, $event);

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function form__layout_builder_configure_section(array &$form, FormStateInterface $form_state) {
    $form['#submit'][] = [static::class, 'form__layout_builder_configure_section__submit'];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function form__layout_builder_configure_block(array &$form, FormStateInterface $form_state) {
    $form['settings']['label']['#access'] = FALSE;
    $form['settings']['label_display']['#access'] = FALSE;
    $form['settings']['admin_label']['#access'] = FALSE;
    $form['settings']['view_mode']['#access'] = FALSE;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function form__layout_builder_configure_section__submit(array &$form, FormStateInterface $form_state) {

    if (empty($form['layout_settings']['layout_entity']['#entity'])) {
      return;
    }

    if (empty($form['layout_settings']['#layout_entity_clone_base'])) {
      return;
    }

    $entity_layout = $form['layout_settings']['layout_entity']['#entity'];

    $entity_layout_base = $form['layout_settings']['#layout_entity_clone_base'];

    $uuid = $entity_layout->uuid();
    $uuid_base = $entity_layout_base->uuid();

    /** @var \Drupal\Core\Layout\LayoutPluginManager $plugin_manager_layout */
    $plugin_manager_layout = \Drupal::service('plugin.manager.core.layout');
    $plugin_manager_layout->clearCachedDefinitions();

    $layout_plugin_id = "design_system__{$uuid}";
    $layout_plugin_id_base = "design_system__{$uuid_base}";
    $layout_plugin_definition = $plugin_manager_layout->getDefinition($layout_plugin_id);

    /** @var \Drupal\layout_builder\Form\ConfigureSectionForm $form_object */
    $form_object = $form_state->getFormObject();

    $section_storage = $form_object->getSectionStorage();

    $sections = $section_storage->getSections();
    $section_storage->removeAllSections(FALSE);

    // Find delta of current section and replace with new plugin.
    foreach ($sections as $delta => $section) {

      $section_layout_plugin_id = $section->getLayoutId();
      if ($section_layout_plugin_id == $layout_plugin_id_base) {

        $section = new Section($layout_plugin_id, $section->getLayoutSettings());
        $sections[$delta] = $section;

      }

      $section_storage->appendSection($section);

    }

    /** @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $tempstore */
    $tempstore = \Drupal::service('layout_builder.tempstore_repository');
    $tempstore->set($section_storage);

    \Drupal::cache('discovery')->delete('layout');

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function form__entity_browser_form(array &$form, FormStateInterface $form_state) {

    $form['#attributes']['class'][] = 'style--dark';

    if (!empty($form['widget']['view'])) {

      $form['widget']['#type'] = 'container';
      $form['widget']['#attributes']['class'][] = 'row';

      $form['widget']['view']['#type'] = 'container';
      $form['widget']['view']['#attributes']['class'][] = 'col-xs-24 col-lg-20';

      $form['widget']['actions'] = [
        '#type' => 'container',
        'inner' => $form['widget']['actions'],
      ];

      $form['widget']['actions']['#attributes']['class'][] = 'col-xs-24 col-lg-4';
      $form['widget']['actions']['#weight'] = -100;
      $form['widget']['actions']['inner']['#attributes']['class'][] = 'sticky';

      // @todo contextual help.
      $form['widget']['actions']['inner']['help'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Click on media to select it. When finished with your selection, click the button above.'),
        '#attributes' => [
          'class' => [
            'help',
            'mt-4',
          ],
        ],
      ];
    }
    elseif (!empty($form['widget']['upload'])) {
      $form['widget']['#type'] = 'container';
      $form['widget']['#attributes']['class'][] = 'row';

      $form['widget']['upload']['#wrapper_attributes']['class'][] = 'col-xs-24 col-lg-20';

      $form['widget']['actions'] = [
        '#type' => 'container',
        'inner' => $form['widget']['actions'],
      ];

      $form['widget']['actions']['#attributes']['class'][] = 'col-xs-24 col-lg-4';
      $form['widget']['actions']['#weight'] = -100;
      $form['widget']['actions']['inner']['#attributes']['class'][] = 'sticky';

    }

  }

  /**
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $complete_form
   *
   * @return array
   */
  public static function processEntityForm(array $element, FormStateInterface $form_state) {
    // @workaround for layout_builder actions missing.
    $element['actions']['#access'] = TRUE;
    return $element;
  }

  /**
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function afterBuildEntityForm(array $element, FormStateInterface $form_state) {
    return $element;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form_id
   */
  protected function viewsFormAlter(array &$form, FormStateInterface $form_state, $form_id) {

    if (!empty($form['header'])) {
      $form['header']['#attributes']['class'][] = 'pb-3';
    }

    if (!empty($form['header']['views_bulk_operations_bulk_form']['multipage'])) {
      $form['header']['views_bulk_operations_bulk_form']['multipage']['#access'] = FALSE;
    }

    if (!empty($form['header']['views_bulk_operations_bulk_form']['actions'])) {

      $unique_view_id = Html::cleanCssIdentifier("views--operations--{$form['#form_id']}");

      $form['header']['views_bulk_operations_bulk_form']['actions']['#attributes']['id'] = 'wrapper--vbo';
      $form['header']['views_bulk_operations_bulk_form']['actions']['#attributes']['class'][] = 'collapse';
      $form['header']['views_bulk_operations_bulk_form']['actions']['#attributes']['class'][] = 'bg-white';
      $form['header']['views_bulk_operations_bulk_form']['actions']['#attributes']['class'][] = 'p-2';
      $form['header']['views_bulk_operations_bulk_form']['actions']['#attributes']['class'][] = 'mt-2';
      $form['header']['views_bulk_operations_bulk_form']['actions']['#attributes']['class'][] = 'border';

      if (!empty($form['header']['views_bulk_operations_bulk_form']['select_all'])) {
        $form['header']['views_bulk_operations_bulk_form']['actions']['select_all'] = $form['header']['views_bulk_operations_bulk_form']['select_all'];
        unset($form['header']['views_bulk_operations_bulk_form']['select_all']);
        $form['header']['views_bulk_operations_bulk_form']['actions']['select_all']['#wrapper_attributes']['class'][] = 'float-right';
      }

      // Remove duplicate actions at bottom of form.
      if (!empty($form['actions'])) {
        unset($form['actions']);
      }

      foreach ($form['header']['views_bulk_operations_bulk_form']['actions'] as $key => &$child) {
        if (!is_array($child) || empty($child['#type']) || !in_array($child['#type'], ['button', 'submit', 'link'])) {
          continue;
        }
        $child['#button_size'] = 'sm';
      }
    }

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form_id
   */
  protected function viewsExposedFormAlter(array &$form, FormStateInterface $form_state, $form_id) {

    /** @var \Drupal\views\ViewExecutable $view */
    $view = $form_state->get('view');

    // Make all date fields date elements.
    if ($view_filters = $view->display_handler->getOption('filters')) {
      foreach ($view_filters as $key => &$filter) {

        if (!empty($filter['plugin_id']) && ($filter['plugin_id'] == 'date')) {

          if (!empty($filter['expose']['identifier'])) {
            if (!empty($form[$filter['expose']['identifier']])) {
              $form[$filter['expose']['identifier']]['#type'] = 'date';
            }
          }
        }

      }
    }

    if (!empty($form['search']) && ($form['search']['#type'] == 'search_api_autocomplete')) {

      if (!empty($form['actions']['reset']['#type'])) {
        $form['actions']['reset']['#icon'] = 'times';
        $form['actions']['reset']['#icon_position'] = 'only';
        $form['actions']['reset']['#button_type'] = 'light';
        $form['actions']['reset']['#type'] = 'button';
        $form['actions']['reset']['#attributes']['title'] = $this->t('Reset these filters');
        $form['actions']['reset']['#attributes']['data-toggle'] = 'tooltip';
        $form['actions']['reset']['#attributes']['class'][] = 'text-primary';
      }

      if (!empty($form['actions']['submit'])) {
        $form['actions']['submit']['#icon_position'] = 'only';
        $form['actions']['submit']['#icon'] = 'search';
      }

    }

    if (!empty($view->getExposedInput())) {
      $form['#attributes']['class'][] = 'ajax--used';
    }

  }

}
