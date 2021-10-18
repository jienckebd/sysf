<?php

namespace Drupal\bd\Entity\Routing;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;
use Drupal\bd\Entity\EntityHelper;
use Drupal\bd\Discovery\ManagerInterface;

/**
 * Normalizes entity routes with common attributes.
 */
class RouteNormalizer extends RouteSubscriberBase {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The discovery service.
   *
   * @var \Drupal\bd\Discovery\ManagerInterface
   */
  protected $discovery;

  /**
   * The routing logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The entity types.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $entityTypes;

  /**
   * Constructs a RouteNormalizer object.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\bd\Discovery\ManagerInterface $discovery
   *   The discovery service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The routing logger channel.
   */
  public function __construct(
    EntityHelper $entity_helper,
    ManagerInterface $discovery,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->discovery = $discovery;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $this->init($collection);
    $this->processFluidConfig($collection);
    $this->processRoutingConfig($collection);
    $this->processEntityConfig($collection);
  }

  /**
   * @param \Symfony\Component\Routing\RouteCollection $collection
   */
  protected function init(RouteCollection $collection) {
    if (empty($this->entityTypes)) {
      $this->entityTypes = $this->entityHelper->getDefinitions();
    }
  }

  /**
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function processEntityConfig(RouteCollection $collection) {

    $entities = $this->entityHelper->getStorage('override')->loadByProperties([
      'bundle' => 'route',
    ]);

    foreach ($entities as $entity) {

      $route_name = $entity->field_id->value;
      if (!$route = $collection->get($route_name)) {
        continue;
      }

      $defaults = $route->getDefaults();
      $options = $route->getOptions();
      foreach ($entity->get('field_key_default') as $delta => $item_field_item) {
        $defaults[$item_field_item->key] = $item_field_item->value;
      }

      foreach ($entity->get('field_key_option') as $delta => $item_field_item) {
        $options[$item_field_item->key] = $item_field_item->value;
      }

      $route->setDefaults($defaults);
      $route->setOptions($options);

    }

  }

  /**
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function processFluidConfig(RouteCollection $collection) {

    $entity_type_config_common = $this->entityHelper->getCommonConfig();

    $map_route_key_method = [
      'option' => 'setOption',
      'default' => 'setDefault',
    ];

    foreach ($collection as $route_name => $route) {

      // @config entity_type.definition.route_pattern.entity
      if (!empty($entity_type_config_common['route']['pattern']['entity'])) {
        foreach ($entity_type_config_common['route']['pattern']['entity'] as $pattern) {
          if (!fnmatch($pattern, $route_name)) {
            continue;
          }

          $route->setOption('_entity_route', TRUE);
        }
      }

      // @config entity_type.definition.route_pattern.rel
      if (!empty($entity_type_config_common['route']['pattern']['rel'])) {
        foreach ($entity_type_config_common['route']['pattern']['rel'] as $rel => $patterns) {
          foreach ($patterns as $pattern) {
            if (!fnmatch($pattern, $route_name)) {
              continue;
            }

            $route_name_parts = explode(".", $route_name);
            $entity_type_id = $route_name_parts[1];

            $route->setOption('_entity_type_id', $entity_type_id);

            if (in_array($rel, ['edit-form', 'add-form', 'delete-form'])) {
              $route->setOption('_entity_form_route', TRUE);
            }
            elseif (in_array($rel, ['canonical'])) {
              $route->setOption('_entity_view_route', TRUE);
            }
            elseif (in_array($rel, ['collection'])) {
              $route->setOption('_entity_list_route', TRUE);
            }

            $route->setOption('_entity_rel', $rel);

          }
        }
      }

      $route_name_pieces = explode('.', $route_name);
      if (count($route_name_pieces) <= 2) {
        continue;
      }

      foreach ($this->entityTypes as $entity_type_id => $entity_type) {

        $config_menu = $entity_type->get('menu');

        if (empty($config_menu['route'])) {
          continue;
        }

        if ($route_name_pieces[0] != 'entity' || ($route_name_pieces[1] != $entity_type_id)) {
          continue;
        }

        // @config entity_type.menu.route
        foreach ($config_menu['route'] as $target_rel => $route_config) {

          $last_piece = end($route_name_pieces);
          $route_rel = str_replace('_', '-', $last_piece);

          // Route ID must either match or be "all".
          if (($route_rel == $target_rel) || ($target_rel == '*')) {

            // Process route configs.
            foreach ($route_config as $route_key => $route_key_config) {
              if (empty($map_route_key_method[$route_key])) {
                $this->logger->warning("Route key @route_key is not valid.", [
                  '@route_key' => $route_key,
                ]);
                continue;
              }

              $method = $map_route_key_method[$route_key];
              foreach ($route_key_config as $route_key_key => $route_key_value) {
                $route->{$method}($route_key_key, $route_key_value);
              }

            }

          }
        }

        if ($title_template = $route->getOption('_title_template')) {
          $route->setDefault('_title', NULL);
          $route->setDefault('_title_callback', '\Drupal\bd\Controller\EntityController::templatedTitleCallback');
        }
      }
    }

  }

  /**
   * @param \Symfony\Component\Routing\RouteCollection $collection
   */
  protected function processRoutingConfig(RouteCollection $collection) {

    if (!$route_alter_config = $this->discovery->getDiscoveryData('routing.alter')) {
      return;
    }

    foreach ($route_alter_config as $route_name => $overrides) {

      if ($overrides === FALSE) {
        if ($route = $collection->get($route_name)) {
          $collection->remove($route_name);
          continue;
        }
      }

      if (!$route = $collection->get($route_name)) {
        $this->logger->warning("Route @route_name could not be altered because it does not exist.", [
          '@route_name' => $route_name,
        ]);
        continue;
      }

      if (!empty($overrides['defaults'])) {
        foreach ($overrides['defaults'] as $key => $value) {
          $route->setDefault($key, $value);
        }
      }

      if (!empty($overrides['options'])) {
        foreach ($overrides['options'] as $key => $value) {
          $route->setOption($key, $value);
        }
      }

      if (!empty($overrides['requirements'])) {
        foreach ($overrides['requirements'] as $key => $value) {
          $route->setRequirement($key, $value);
        }
      }

    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -100];
    return $events;
  }

}
