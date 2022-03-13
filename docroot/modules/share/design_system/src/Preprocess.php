<?php

namespace Drupal\design_system;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\design_system\Element\Normalizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Database\Connection;
use Drupal\bd\Entity\EntityAnalyzer;

/**
 * Provides preprocess logic.
 */
class Preprocess implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The entity analyzer.
   *
   * @var \Drupal\bd\Entity\EntityAnalyzer
   */
  protected $entityAnalyzer;

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The admin contexts.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current request pulled from request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

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
   * The route object.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $routeObject;

  /**
   * The route name.
   *
   * @var string
   */
  protected $routeName;

  /**
   * Whether or not this is admin route.
   *
   * @var bool
   */
  protected $isAdmin;

  /**
   * The entity from route or NULL.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entityFromRoute;

  /**
   * Constructs a Preprocess object.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity storage for views.
   * @param \Drupal\design_system\DesignSystem $design_system
   *   The design system.
   * @param \Drupal\bd\Entity\EntityAnalyzer $entity_analyzer
   *   The entity analyzer.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache back end.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityAnalyzer $entity_analyzer,
    DesignSystem $design_system,
    Connection $database,
    RouteMatchInterface $route_match,
    PathMatcherInterface $path_matcher,
    AdminContext $admin_context,
    RequestStack $request_stack,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->entityAnalyzer = $entity_analyzer;
    $this->designSystem = $design_system;
    $this->database = $database;
    $this->routeMatch = $route_match;
    $this->pathMatcher = $path_matcher;
    $this->adminContext = $admin_context;
    $this->requestStack = $request_stack;
    $this->request = $request_stack->getCurrentRequest();
    $this->cache = $cache;
    $this->logger = $logger;
    $this->routeObject = $this->routeMatch->getRouteObject();
    $this->routeName = $this->routeMatch->getRouteName();
    $this->isAdmin = $this->adminContext->isAdminRoute();
    $this->entityFromRoute = $this->designSystem->getEntityFromRoute();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.helper'),
      $container->get('entity.analyzer'),
      $container->get('design.system'),
      $container->get('database'),
      $container->get('current_route_match'),
      $container->get('path.matcher'),
      $container->get('router.admin_context'),
      $container->get('request_stack'),
      $container->get('cache.default'),
      $container->get('logger.channel.design_system')
    );
  }

  /**
   * @param array $variables
   */
  public function preprocess(array &$variables) {

    $hook_id = $variables['theme_hook_original'];

    if (method_exists($this, $hook_id)) {
      $this->{$hook_id}($variables);
    }

    if (!empty($variables['element']['#input']) && ($hook_id != 'form_element')) {
      $this->input($variables);
    }

    if (fnmatch("menu__*", $hook_id)) {
      $this->menu($variables);
    }

    if (!empty($variables['attributes']['style'])) {
      Normalizer::convertStyleArrayToString($variables);
    }

  }

  /**
   * @param array $variables
   */
  protected function entity(array &$variables) {

    $entity_type_id = $variables['elements']['#entity_type_id'];

    if (empty($variables['elements']["#{$entity_type_id}"])) {
      return;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $variables['elements']["#{$entity_type_id}"];

    $entity_metadata = $this->entityAnalyzer->getEntityMetaData($entity, $variables);

    foreach ($entity_metadata as $key => $value) {

      $key_clean = Html::cleanCssIdentifier("data-{$key}");
      $variables['attributes'][$key_clean] = $value;

    }

    foreach ($entity->getFieldDefinitions() as $field_name => $field_definition) {

      if (!method_exists($field_definition, 'getThirdPartySettings')) {
        continue;
      }

      $bd_config = $field_definition->getThirdPartySettings('bd');
      if (!empty($bd_config['behavior']['dom']['attribute'])) {
        if ($value = $entity->get($field_name)->value) {
          $variables['attributes'][$bd_config['behavior']['dom']['attribute']] = $value;
        }
      }

    }

    $variables += ['content' => []];
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }
    $variables['content']['#type'] = 'container';

    if (!empty($variables['title_prefix'])) {
      $variables['content']['title_prefix'] = $variables['title_prefix'];
      $variables['content']['title_prefix']['#weight'] = -1000;
    }
    if (!empty($variables['title_suffix'])) {
      $variables['content']['title_suffix'] = $variables['title_suffix'];
      $variables['content']['title_suffix']['#weight'] = -999;
    }

    $variables['content']['#attributes'] = $variables['attributes'];
    if (!empty($variables['wrapper_tag'])) {
      $variables['content']['#wrapper_tag'] = $variables['wrapper_tag'];
    }

    if ($entity_type_id == 'menu_link_content') {
      $this->processMenuLinkContent($variables, $entity);
    }

    if ($entity->hasField('container')) {
      $this->recurseAttachContainer($variables['content'], $entity);
    }

  }

  /**
   * @param array $variables
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function processMenuLinkContent(array &$variables, ContentEntityInterface $entity) {

    if (isset($variables['content']['title']['#access']) && ($variables['content']['title']['#access'] === FALSE)) {
      $variables['content']['title']['#access'] = TRUE;
    }

    if (!$entity->get('link')->isEmpty()) {

      /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem $link_field_item */
      $field_item_link = $entity->get('link')->get(0);
      $variables['content']['#wrapper_tag'] = 'a';
      $variables['content']['#attributes']['href'] = $field_item_link->getUrl()->toString();

    }

  }

  /**
   * @param array $element
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  protected function recurseAttachContainer(array &$element, ContentEntityInterface $entity) {

    $field_items_container = $entity->get('container');
    if ($field_items_container->isEmpty()) {
      return;
    }

    $containers = [];
    foreach ($field_items_container as $delta => $field_item) {
      $containers[$delta] = $field_item->entity;
    }

    $containers_reversed = array_reverse($containers);

    /**
     * @var int $delta
     * @var \Drupal\block_content\BlockContentInterface $entity_container
     */
    foreach ($containers_reversed as $delta => $entity_container) {

      /** @var \Drupal\design_system\Entity\Entity\Dom $entity_dom_style */
      if (!$entity_dom_style = $entity_container->field_dom_style->entity) {
        continue;
      }

      if ($entity_container->field_dom_style->isEmpty()) {
        continue;
      }

      foreach ($entity_container->field_dom_style as $delta_dom_style => $field_item_dom_style) {

        $entity_dom_style = $field_item_dom_style->entity;

        if ($delta == 0) {
          $entity_dom_style->bindToElement($element);
        }
        else {
          $element = [
            '#type' => 'container',
            'inner' => $element,
          ];
          $entity_dom_style->bindToElement($element);
        }

      }

    }

  }

  /**
   * @param array $variables
   */
  protected function field(array &$variables) {
    $variables['wrapper_tag'] = 'div';
    $variables['attributes']['class'][] = 'field';

    $variables['title_attributes']['class'][] = 'field--label';
    $variables['content_attributes']['class'][] = 'field--content';

    if (!empty($variables['element']['#items'])) {
      /** @var \Drupal\Core\Field\FieldItemListInterface $field_item_list */
      $field_item_list = $variables['element']['#items'];
      $field_definition = $field_item_list->getFieldDefinition();
      $variables['attributes']['data-field-type'] = $field_definition->getType();
      $variables['attributes']['data-field-name'] = $field_definition->getName();
      $variables['attributes']['data-label-display'] = $variables['label_display'];
      $variables['attributes']['data-multiple'] = (int) $variables['multiple'];
      $variables['attributes']['data-count'] = $field_item_list->count();

      foreach ($field_item_list as $delta => $field_item) {

        if (empty($variables['items'][$delta])) {
          continue;
        }

        $build_field_item = &$variables['items'][$delta];
        if (!empty($build_field_item['attributes']) && ($build_field_item['attributes'] instanceof Attribute)) {

          /** @var \Drupal\Core\Template\Attribute $field_item_attributes */
          $field_item_attributes = $build_field_item['attributes'];

          $field_item_attributes->addClass('field--item');
          $field_item_attributes->setAttribute('data-delta', $delta);

        }

      }

    }

  }

  /**
   * @param array $variables
   */
  protected function region(array &$variables) {

    $element_region = $variables['elements'];

    $region_id = $element_region['#region'];
    $variables['attributes']['class'][] = 'region';
    $variables['attributes']['data-region-id'] = $region_id;

  }

  /**
   * @param array $variables
   */
  protected function html(array &$variables) {

    $variables['#attached']['library'][] = 'alpha/global';
    $variables['#attached']['library'][] = 'alpha/ckeditor';

    $is_front = $this->pathMatcher->isFrontPage();
    $is_entity_form_route = FALSE;

    $id = NULL;
    $class_list = [];
    $attribute_list = [];

    if ($is_front) {
      $id = 'home';
      $class_list[] = 'is-front';
    }
    else {
      $class_list[] = 'is-front-no';
    }

    if (!empty($variables['logged_in'])) {
      $class_list[] = 'is-auth';
    }
    else {
      $class_list[] = 'is-auth-no';
    }

    if ($this->isAdmin) {
      $class_list[] = 'is-admin';
    }
    else {
      $class_list[] = 'is-admin-no';
    }

    if ($this->routeObject->getOption('_entity_route')) {
      $class_list[] = 'entity';
    }

    if ($this->routeObject->getOption('_entity_form_route')) {
      $class_list[] = 'entity-form';
    }

    if ($this->routeObject->getOption('_entity_view_route')) {
      $class_list[] = 'entity-view';
    }

    if ($entity_type_id_from_route = $this->routeObject->getOption('_entity_type_id')) {
      $attribute_list['data-entity-type-id'] = $entity_type_id_from_route;
    }

    if (!empty($class_list)) {
      foreach ($class_list as $class_list_name) {
        $variables['attributes']['class'][] = Html::cleanCssIdentifier($class_list_name);
      }
    }

    if ($theme_doms = \Drupal::service('theme_handler')->getDomDependencyEntityForTheme()) {
      /**
       * @var int $entity_id
       * @var \Drupal\design_system\Entity\Entity\Dom $entity
       */
      foreach ($theme_doms as $entity_id => $entity) {
        $entity->bindToElement($variables, 'html_attributes');
      }
    }

  }

  /**
   * @param array $variables
   */
  protected function page(array &$variables) {

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'screen',
        ],
        'id' => 'screen',
      ],
    ];

    foreach (Element::children($variables['page']) as $theme_region_id) {

      $child = &$variables['page'][$theme_region_id];
      $build[$theme_region_id] = $child;

    }

    $variables['build'] = $build;

  }

  /**
   * @param array $variables
   */
  protected function menu(array &$variables) {
    $this->menuCommon($variables);
  }

  /**
   * @param array $variables
   */
  protected function menu__extras(array &$variables) {
    $this->menuCommon($variables);
  }

  /**
   * @param array $variables
   */
  protected function menuCommon(array &$variables) {
    $variables['attributes']['class'][] = 'menu';
    $variables['attributes']['data-menu-name'] = $variables['menu_name'] ?? NULL;
    $variables['attributes']['data-child-count'] = count($variables['items']);
  }

  /**
   * @param $variables
   */
  protected function status_messages(&$variables) {

    $variables['attributes']['data-drupal-messages'] = '';
    $variables['attributes']['class'] = 'messages--wrapper';

    $variables['status_headings'] = [
      'status' => $this->t('Status'),
      'warning' => $this->t('Alert'),
      'error' => $this->t('Error'),
      'legal' => $this->t('Legal Notice'),
    ];

    if (empty($variables['message_list'])) {
      return;
    }

    $enable_autohide = TRUE;
    $message_strings = [];
    foreach ($variables['message_list'] as $type => $type_messages) {
      foreach ($type_messages as $message) {
        $message_strings[] = is_string($message) ? $message : $message->__toString();
      }
    }

    if ($safe_text = $this->designSystem->getConfigKey('alert.safe_text')) {
      foreach ($safe_text as $text) {
        foreach ($message_strings as $message_string) {
          if (preg_match("/{$text}/i", $message_string)) {
            $enable_autohide = FALSE;
          }
        }
      }
    }

    $variables['attributes']['data-auto-hide'] = $enable_autohide ? 1 : 0;
  }

  /**
   * @param $variables
   */
  protected function block(&$variables) {
    $variables['attributes']['data-block-plugin-id'] = $variables['elements']['#plugin_id'];

    $specific_hook = "block__{$variables['plugin_id']}";
    if (method_exists($this, $specific_hook)) {
      $this->{$specific_hook}($variables);
    }

  }

  /**
   * @param array $variables
   */
  protected function container(array &$variables) {

    if (!empty($variables['element']['#parents'])) {
      $form_field_key = end($variables['element']['#parents']);
      $variables['attributes']['data-form-field-key'] = $form_field_key;
    }

    $element = &$variables['element'];

    if (!empty($element['#containers'])) {
      foreach ($element['#containers'] as $key => $container_config) {
        $variables['children'] = [
          '#type' => 'container',
          '#attributes' => isset($container_config['attributes']) ? $container_config['attributes'] : [],
          '#wrapper_tag' => isset($container_config['wrapper_tag']) ? $container_config['wrapper_tag'] : [],
          '#children' => $variables['children'],
        ];
      }
    }

    $variables['wrapper_tag'] = 'div';
    if (!empty($element['#wrapper_tag'])) {
      $variables['wrapper_tag'] = $element['#wrapper_tag'];
    }

  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  protected function views_view_unformatted(array &$variables) {
    if (!empty($variables['rows'])) {
      foreach ($variables['rows'] as $key => &$row) {
        if (empty($row['attributes']['class'])) {
          $row['attributes']['class'] = [];
        }
        $row['attributes']['class'][] = ($key % 2 === 0) ? "views-row--odd" : "views-row--even";
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  protected function form(array &$variables) {
    if (!empty($variables['element']['#view'])) {
      /** @var \Drupal\views\ViewExecutable $view */
      $view = $variables['element']['#view'];
      $variables['attributes']['data-view-id'] = $view->id();
      $variables['attributes']['data-display-id'] = $view->current_display;
    }
  }

  /**
   * @param array $variables
   */
  protected function form_element(array &$variables) {

    if (!empty($variables['element']['#attributes']['id'])) {
      $id = $variables['element']['#attributes']['id'];
      $label_id = "label-{$id}";
      $variables['label']['#attributes']['id'] = $label_id;
    }

    if (!isset($variables['element']['#value']) || ($variables['element']['#value'] == '')) {
      $variables['attributes']['class'][] = 'state--empty';
    }

    $form_field_key = NULL;
    if (!empty($variables['element']['#parents'])) {
      $last_parent = end($variables['element']['#parents']);
      if (is_string($last_parent)) {
        $form_field_key = $last_parent;
        $variables['attributes']['data-form-field-key'] = $form_field_key;
      }
    }

    $properties_copy_to_attributes = [
      '#multiple',
      '#autocomplete',
      '#required',
    ];

    foreach ($properties_copy_to_attributes as $property) {
      if (isset($variables['element'][$property])) {
        $property_name_dom_safe = ltrim($property, '#');
        $property_name_dom_safe = "data-{$property_name_dom_safe}";

        $property_value = $variables['element'][$property];
        if (is_bool($property_value)) {
          $property_value = (int) $property_value;
        }

        $variables['attributes'][$property_name_dom_safe] = $property_value;
      }
    }

    if (!empty($variables['element']['#array_parents'])) {
      $form_field_id = implode('.', $variables['element']['#array_parents']);
      $variables['attributes']['data-form-field-id'] = $form_field_id;
    }

    if (!empty($variables['element']['#type'])) {
      $form_field_type = $variables['element']['#type'];
      $variables['attributes']['data-form-field-type'] = $form_field_type;
    }

    if (!empty($variables['element']['#required'])) {
      $variables['attributes']['class'][] = 'form-item--required';
    }

    $confirm_label_type = [
      'checkbox',
      'radio',
    ];

    if (in_array($variables['element']['#type'], $confirm_label_type)) {
      $variables['label_display'] = 'after';
      if ($variables['suffix'] = '&nbsp;') {
        unset($variables['suffix']);
      }
      if (empty($variables['element']['#title'])) {
        $variables['label'] = [
          '#type' => 'html_tag',
          '#tag' => 'label',
          '#value' => '',
          '#attributes' => [
            'for' => !empty($variables['element']['#id']) ? $variables['element']['#id'] : '@todo',
            'class' => [
              'visually-hidden',
            ],
          ],
        ];
        $variables['label_display'] = 'invisible';
      }
    }

    $autocomplete_type = [
      'search',
      'entity_autocomplete',
      'search_api_autocomplete',
    ];
    if (in_array($variables['element']['#type'], $autocomplete_type) || !empty($variables['element']['#autocomplete_route_name'])) {
      $variables['attributes']['class'][] = 'form-item--autocomplete';
    }

    if (!empty($variables['element']['#title']) && (empty($variables['element']['#title_display']) || $variables['element']['#title_display'] != 'invisible')) {
      $variables['attributes']['class'][] = 'label--yes';
    }
    else {
      $variables['attributes']['class'][] = 'label--no';
    }

    $form_element_type_support_label_inside = [
      'textfield',
      'textarea',
      'select',
      'entity_autocomplete',
      'datetime',
      'date',
      'time',
      'weight',
      'number',
      'tel',
      'email',
      'password',
      'search_api_autocomplete',
    ];

    if (in_array($variables['element']['#type'], $form_element_type_support_label_inside)) {
      $variables['element']['#title_display'] = 'inside';
    }

    if (!empty($variables['element']['#title_display'])) {
      $variables['attributes']['class'][] = Html::cleanCssIdentifier("label--{$variables['element']['#title_display']}");

      if (in_array($variables['element']['#title_display'], ['inside'])) {
        $variables['label_display'] = 'before';
      }
    }

    if (!empty($variables['element']['#description_display'])) {
      $variables['attributes']['class'][] = Html::cleanCssIdentifier("description--{$variables['element']['#description_display']}");

      if (in_array($variables['element']['#description_display'], ['hover', 'focus', 'hover_focus'])) {
        $variables['description_display'] = 'after';
      }
    }

    if ($variables['element']['#type'] == 'managed_file') {
      $d = 1;
    }

    $form_element_type_no_margin = [
      'radio',
      'checkbox',
    ];
    $form_key_no_margin = [
      'format',
      'search',
    ];
    if (!empty($variables['element']['#format'])) {
      // Text format is a textarea and select field combined. The mb-4 class is
      // in its twig template for now.
      $variables['attributes']['class'][] = 'mb-2';
    }
    elseif (!in_array($variables['element']['#type'], $form_element_type_no_margin) && !in_array($form_field_key, $form_key_no_margin)) {
      $variables['attributes']['class'][] = 'mb-3';
    }

    $form_element_type_label_not_bold = [
      'checkbox',
      'radio',
    ];
    if (!in_array($variables['element']['#type'], $form_element_type_label_not_bold)) {
      $variables['label']['#attributes']['class'][] = 'font-weight-bold';
    }

  }

  /**
   * @param array $variables
   */
  protected function input(array &$variables) {

    $form_control_type = [
      'textfield',
      'select',
      'password',
      'search_api_autocomplete',
      'textarea',
      'entity_autocomplete',
      'url',
      'tel',
      'number',
      'search',
      'email',
      'date',
      'time',
    ];

    $form_control_submit = [
      'submit',
      'button',
    ];

    $skip_input_type = [
      'submit',
      'button',
      'container',
      'fieldset',
      'details',
      'webform_email_confirm',
    ];

    if (!empty($variables['element']['#type'])) {

      if (in_array($variables['element']['#type'], $form_control_type)) {
        $variables['attributes']['class'][] = 'form-control';
      }

      if (in_array($variables['element']['#type'], $form_control_submit)) {
        $variables['attributes']['class'][] = 'btn';

        $button_id = !empty($variables['element']['#array_parents']) ? end($variables['element']['#array_parents']) : NULL;
        $button_text = '';
        if (!empty($variables['element']['#title'])) {
          $button_text = is_object($variables['element']['#title']) ? $variables['element']['#title']->__toString() : $variables['element']['#title'];
        }

        $button_id_class_map = [
          'submit' => 'btn-primary',
        ];

        if (!empty($button_id_class_map[$button_id])) {
          $variables['attributes']['class'][] = 'btn-primary';
        }

        if (!empty($variables['element']['#value'])) {
          // Provides required value to button template.
          $variables['value'] = $variables['element']['#value'];
        }
      }

      if ($variables['element']['#type'] == 'email') {
        unset($variables['attributes']['aria-describedby']);
      }

      if (in_array($variables['element']['#type'], $skip_input_type)) {
        return;
      }
    }

    if (!empty($variables['element']['#attributes']['id'])) {
      $id = $variables['element']['#attributes']['id'];
      $label_id = "label-{$id}";
      $variables['attributes']['aria-labelledby'] = $label_id;
    }

  }

  /**
   * @param array $variables
   */
  protected function form_element_label(array &$variables) {
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  protected function table(&$variables) {
    if (empty($variables['attributes']['class'])) {
      $variables['attributes']['class'] = [];
    }
    if (is_string($variables['attributes']['class'])) {
      $variables['attributes']['class'] = [$variables['attributes']['class']];
    }
    $variables['attributes']['class'][] = 'table';
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  protected function views_view_table(&$variables) {
    $variables['attributes']['class'][] = 'table';
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  protected function field_ui_table(&$variables) {
    $variables['attributes']['class'][] = 'table';
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  protected function block__local_actions_block(&$variables) {

    if (isset($variables['content']['#markup'])) {
      return;
    }

    $variables['content_attributes']['class'][] = 'w--d-inline-block';

    if (!empty($variables['content'])) {
      foreach ($variables['content'] as $key => &$child) {
        if (empty($child['#link'])) {
          continue;
        }
        $child['#link']['localized_options']['attributes']['class'][] = 'px-4 py-2';
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  protected function block__local_tasks_block(&$variables) {

    if (!empty($variables['content']['#primary'])) {

      foreach ($variables['content']['#primary'] as $id => &$child) {
        if (is_array($child) && !empty($child['#theme'])) {
          $child['#link']['localized_options']['attributes']['class'][] = 'border-right';
          $child['#link']['localized_options']['attributes']['class'][] = 'border-darker';
          $child['#link']['localized_options']['attributes']['class'][] = 'py-2 px-4 hover-bg-darker';
        }
      }

      $variables['content']['#primary']['#type'] = 'container';
      $variables['content']['#primary']['#attributes']['class'][] = 'local-task-block--primary';
      $variables['content']['#primary']['#attributes']['class'][] = 'd-flex';
      $variables['content']['#primary']['#attributes']['class'][] = 'position-relative';
      $variables['content']['#primary']['#attributes']['class'][] = 'zi-2';
    }

    if (!empty($variables['content']['#secondary'])) {

      foreach ($variables['content']['#secondary'] as $id => &$child) {
        if (is_array($child) && !empty($child['#theme'])) {
          $child['#link']['localized_options']['attributes']['class'][] = 'border-right';
          $child['#link']['localized_options']['attributes']['class'][] = 'border-darker';
          $child['#link']['localized_options']['attributes']['class'][] = 'py-2 px-4 hover-bg-darker';
        }
      }

      $variables['content']['#secondary']['#type'] = 'container';
      $variables['content']['#secondary']['#attributes']['class'][] = 'local-task-block--secondary';
      $variables['content']['#secondary']['#attributes']['class'][] = 'd-flex';
      $variables['content']['#secondary']['#attributes']['class'][] = 'zi-1';
      $variables['content']['#secondary']['#attributes']['class'][] = 'border-top';
      $variables['content']['#secondary']['#attributes']['class'][] = 'border-darker';
      $variables['content']['#secondary']['#attributes']['class'][] = 'border-right';
    }

  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  protected function pager(array &$variables) {
    $variables['attributes']['class'][] = 'pager';
    $variables['attributes']['role'] = 'navigation';
    $variables['ul_attributes']['class'][] = 'js-pager__item';
    $variables['ul_attributes']['class'][] = 'pager__items';
    $variables['ul_attributes']['class'][] = 'd-flex';
    $variables['ul_attributes']['class'][] = 'justify-content-center';
    $variables['li_attributes']['class'][] = 'pager__item';
    $variables['li_attributes']['class'][] = 'mr-1';
    $variables['li_attributes']['class'][] = 'bg-secondary';
    $variables['li_attributes']['class'][] = 'text-white';
    $variables['ul_attributes'] = new Attribute($variables['ul_attributes']);
    $variables['li_attributes'] = new Attribute($variables['li_attributes']);
    $variables['ellipses']['next'] = FALSE;
    $variables['ellipses']['back'] = FALSE;
  }

  /**
   * @param array $variables
   */
  protected function social_media_links_platforms(array &$variables) {

    $map_platform_class = [
      'facebook' => 'blue-green',
      'snapchat' => 'yellow',
      'instagram' => 'warning',
      'youtube' => 'danger',
      'twitter' => 'blue-light',
      'linkedin' => 'primary',
      'print' => 'dark',
      'email' => 'secondary',
    ];

    foreach ($variables['platforms'] as $id => &$child) {
      /** @var \Drupal\Core\Template\Attribute $platform_attributes */
      $platform_attributes = $child['attributes'];

      $plugin_definition = $child['instance']->getPluginDefinition();
      $url_prefix = '';
      if (!empty($plugin_definition['urlPrefix'])) {
        $url_prefix = $url_prefix = $plugin_definition['urlPrefix'];
      }
      elseif ($id == 'email') {
        $url_prefix = 'mailto:';
      }
      $value = $child['instance']->getValue();
      $url = "{$url_prefix}{$value}";
      $child['url'] = $this->designSystem->entityTokenReplace($url, FALSE);

      if (!empty($child['attributes']['title'])) {
        $platform_attributes->setAttribute('data-tooltip', 'bottom');
      }

      $platform_attributes->addClass("d-inline-block");

      if (!empty($map_platform_class[$id])) {
        $color_class = "bg-{$map_platform_class[$id]}";
      }
      else {
        $color_class = 'bg-off-white';
      }
      $platform_attributes->addClass($color_class);
    }

  }

  /**
   * @param array $variables
   */
  protected function field_multiple_value_form(array &$variables) {
    if (!empty($variables['table']['#header'][0]['data'])) {
      $variables['table']['#header'][0]['data'] = [
        '#type' => 'label',
        '#title' => $variables['element']['#title'],
        '#title_display' => 'before',
      ];
    }

    if (!empty($variables['button'])) {
      $variables['button']['#attributes']['class'][] = 'mt-2';
      $variables['button']['#attributes']['class'][] = 'pt-2';
      $variables['button']['#button_size'] = 'sm';
      $variables['button']['#icon'] = 'plus';
    }

    if (!empty($variables['elements'])) {
      foreach ($variables['elements'] as $key => &$child) {

        if (!empty($child['#theme_wrappers']) && in_array('fieldset', $child['#theme_wrappers'])) {

          // Make multi value fields use container with label like normal field
          // wrappers. Designed around date form wrapper but needs to apply
          // elsewhere.
          $child['#type'] = 'container';

          // This is single value.
          $child['value']['date']['#title'] = $variables['element']['#title'];
          $child['value']['date']['#title_display'] = 'before';
          $child['value']['time']['#title_display'] = 'before';

          // If there's a description, containers can't have descriptions. So
          // move to bottom as new element.
          if (!empty($child['#description'])) {
            $child['description'] = [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#value' => $child['#description'],
              '#attributes' => [
                'class' => [
                  'description',
                ],
              ],
              '#weight' => 1000,
            ];
          }

          // Disable fieldset on field wrappers.
          if (!empty($child['#theme_wrappers'])) {
            unset($child['#theme_wrappers']);
          }
        }

        // If element has length indicator, move description to new element.
        if (!empty($child['length_indicator'])) {
          if (!empty($child['value']['#description'])) {
            $child['description'] = [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#value' => $child['value']['#description'],
              '#attributes' => [
                'class' => [
                  'description',
                ],
              ],
              '#weight' => 1000,
            ];
            unset($child['value']['#description']);
          }
        }
      }
    }
  }

  /**
   * @param array $variables
   */
  protected function datetime_form(array &$variables) {
    $variables['attributes']['class'][] = 'row';
  }

}
