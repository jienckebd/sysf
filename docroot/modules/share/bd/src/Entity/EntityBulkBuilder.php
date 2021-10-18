<?php

namespace Drupal\bd\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\bd\Config\ProcessorInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class EntityBulkBuilder.
 */
class EntityBulkBuilder {

  use StringTranslationTrait;

  /**
   * @todo move to config.
   *
   * @var array
   */
  const TEMPLATE = [
    'sitemap' => [
      'label' => 'Research',
      'description' => '',
      'destination' => [
        'entity_type' => 'node',
        'bundle' => 'landing_page',
      ],
    ],
  ];

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  protected $entityHelper;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The single entity builder.
   *
   * @var \Drupal\bd\Entity\EntityBuilder
   */
  protected $entityBuilder;

  /**
   * The config processor.
   *
   * @var \Drupal\bd\Config\ProcessorInterface
   */
  protected $configProcessor;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * EntityBuilder constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\bd\Entity\EntityBuilder $entity_builder
   *   The entity builder.
   * @param \Drupal\bd\Config\ProcessorInterface $config_processor
   *   The config processor.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function __construct(
    EntityHelper $entity_helper,
    EntityFieldManagerInterface $entity_field_manager,
    EntityBuilder $entity_builder,
    ProcessorInterface $config_processor,
    SerializerInterface $serializer,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityBuilder = $entity_builder;
    $this->configProcessor = $config_processor;
    $this->serializer = $serializer;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   *
   */
  public function buildFromCsv($csv_path) {

  }

  /**
   *
   */
  public function buildTemplate($template_id, array $config) {

    if (empty($config['children'])) {
      throw new \Exception('Missing children key.');
    }

    if (empty($config['root'])) {
      throw new \Exception('Missing root key.');
    }

    $entity_root = $this->entityHelper->getStorage($config['root']['entity_type'])
      ->load($config['root']['entity_id']);

    if (empty($entity_root)) {
      throw new \Exception('Unable to load root entity.');
    }

    $template_config = $this->getTemplateConfig($template_id);

    $revision_ids = $this->recurseProcessChildren($entity_root, $config['children'], $config, $template_config);

    $state_id = "entity.bulk_builder.template.{$template_id}";
    \Drupal::state()->set($state_id, $revision_ids);

    $this->logger->notice("Entity bulk builder created @count entities for template @template_id.", [
      '@count' => count($revision_ids),
      '@template_id' => $template_id,
    ]);

    return TRUE;

  }

  /**
   *
   */
  protected function recurseProcessChildren(EntityInterface $entity_parent, array &$config_children, array &$config_all, array &$template_config) {

    $revision_ids = [];
    $weight = 0;
    foreach ($config_children as $child_alias_key => &$child_config) {
      $weight++;

      $root_alias = $entity_parent->get('path')->alias;
      $root_menu_link = menu_ui_get_menu_link_defaults($entity_parent);

      $entity_alias = "{$root_alias}/{$child_alias_key}";

      $target_entity_type_id = $template_config['destination']['entity_type'];
      $target_bundle = $template_config['destination']['bundle'];

      $entity_type = $this->entityHelper->getDefinition($target_entity_type_id);
      $entity_storage = $this->entityHelper->getStorage($target_entity_type_id);

      $entity_key_label = $entity_type->getKey('label');
      $entity_key_bundle = $entity_type->getKey('bundle');

      $entity_values = [
        $entity_key_label => $child_config['label'],
        $entity_key_bundle => $target_bundle,
      ];

      if (!empty($child_config['template'])) {
        $entity_values['layout_selection'] = $child_config['template'];
      }

      if (!$entity = $entity_storage->loadByProperties($entity_values)) {
        $entity = $entity_storage->create($entity_values);
      }
      else {
        $entity = reset($entity);
      }

      $entity->get('path')->pathauto = FALSE;
      $entity->get('path')->alias = $entity_alias;

      $menu_link_values = menu_ui_get_menu_link_defaults($entity);
      $menu_link_values['title'] = $entity->label();
      $menu_link_values['menu_name'] = 'main';
      $menu_link_values['bundle'] = 'main';
      $menu_link_values['link_type'] = 'ajax_history';
      $menu_link_values['parent'] = $root_menu_link['id'];
      $menu_link_values['weight'] = $weight;

      $entity->save();
      $revision_ids[] = $entity->getRevisionId();

      $this->saveMenuLink($entity, $menu_link_values);

      if (!empty($child_config['children'])) {
        $revision_ids_children = $this->recurseProcessChildren($entity, $child_config['children'], $config_all, $template_config);
        $revision_ids = array_merge($revision_ids, $revision_ids_children);
      }

    }

    return $revision_ids;
  }

  /**
   *
   */
  public function saveMenuLink(ContentEntityInterface $node, array $values) {

    $entity_storage_menu_link = $this->entityHelper->getStorage('menu_link_content');

    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $entity */
    if (!empty($values['entity_id'])) {
      $entity = $entity_storage_menu_link->load($values['entity_id']);
      if ($entity->isTranslatable()) {
        if (!$entity->hasTranslation($node->language()->getId())) {
          $entity = $entity->addTranslation($node->language()->getId(), $entity->toArray());
        }
        else {
          $entity = $entity->getTranslation($node->language()->getId());
        }
      }
    }
    else {
      // Create a new menu_link_content entity.
      $entity = $entity_storage_menu_link->create([
        'link' => ['uri' => 'entity:node/' . $node->id()],
        'langcode' => $node->language()->getId(),
      ]);
      $entity->enabled->value = 1;
    }
    $entity->get('title')->value = trim($values['title']);
    $entity->description->value = trim($values['description']);
    $entity->menu_name->value = $values['menu_name'];
    $entity->get('parent')->value = $values['parent'];
    $entity->set('bundle', $values['bundle']);
    $entity->set('link_type', $values['link_type']);
    $entity->weight->value = isset($values['weight']) ? $values['weight'] : 0;
    $entity->isDefaultRevision($node->isDefaultRevision());
    $entity->save();
  }

  /**
   * @param $template_id
   *
   * @return mixed
   * @throws \Exception
   */
  public function getTemplateConfig($template_id) {

    if (empty(static::TEMPLATE[$template_id])) {
      throw new \Exception('Invalid template ID.');
    }

    return static::TEMPLATE[$template_id];
  }

  /**
   *
   */
  public function resetTemplate($template_id) {

    if (!$template_config = $this->getTemplateConfig($template_id)) {
      return FALSE;
    }

    $entity_type_id = $template_config['destination']['entity_type'];
    $state_id = "entity.bulk_builder.template.{$template_id}";
    $revision_ids = \Drupal::state()->get($state_id, []);

    $entity_storage_destination = $this->entityHelper->getStorage($entity_type_id);

    $entities = [];
    foreach ($revision_ids as $entity_id) {

      if ($entity = $entity_storage_destination->loadRevision($entity_id)) {
        $entities[] = $entity_storage_destination->loadRevision($entity_id);
      }
      else {
        $this->logger->warning("Entity bulk builder unable to load entity ID @entity_id for template @template_id.", [
          '@entity_id' => $entity_id,
          '@template_id' => $template_id,
        ]);
      }

    }

    if (!empty($entities)) {
      $entity_storage_destination->delete($entities);
      $this->logger->notice("Entity bulk builder reset and deleted @count entities for template @template_id.", [
        '@count' => count($entities),
        '@template_id' => $template_id,
      ]);
    }
    else {
      $this->logger->notice("Entity bulk builder found no entities to delete for template @template_id.", [
        '@template_id' => $template_id,
      ]);
    }

    \Drupal::state()->delete($state_id);

  }

  /**
   * {@inheritdoc}
   */
  public function buildByConfigAll() {
    if (!$entity_definitions = \Drupal::configFactory()->getConfig('bd.entity')) {
      return;
    }

    foreach ($entity_definitions['definition'] as $entity_type_id => $entity_definitions_of_entity_type) {

      $entity_storage = $this->entityHelper->getStorage($entity_type_id);
      $entity_type = $this->entityHelper->getDefinition($entity_type_id);

      foreach ($entity_definitions_of_entity_type as $entity_definition) {

        if (!$entity = $entity_storage->loadByProperties($entity_definition)) {
          $entity = $entity_storage->create($entity_definition);
          $entity->save();
        }
      }

    }

  }

  /**
   * @param $entity_type_id
   * @param $bundle_match
   * @param array $entity_definition_config
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildByConfig($entity_type_id, $bundle_match = NULL) {

    $config_key = !empty($entity_type_id) ? "definition.{$entity_type_id}" : "definition";

    $entity_config = \Drupal::configFactory()->getConfig('bd.entity');
    if (!$entity_definition_config = $entity_config->get($config_key)) {
      return FALSE;
    }

    $entity_type = $this->entityHelper->getDefinition($entity_type_id);
    $entity_storage = $this->entityHelper->getStorage($entity_type_id);
    $bundle_key = $entity_type->getkey('bundle');

    foreach ($entity_definition_config as $key => $entity_definition) {

      $machine_name = $entity_definition['machine_name'];
      $bundle_id = $entity_definition['bundle'];

      // If machine name is set but no label, use machine name as label.
      if (!isset($entity_definition['label']) && isset($entity_definition['machine_name'])) {
        $entity_definition['label'] = $entity_definition['machine_name'];
      }

      if (!empty($bundle_match)) {
        if ($entity_definition['bundle'] != $bundle_match) {
          continue;
        }
      }

      $load_properties = [
        'machine_name' => $machine_name,
        $bundle_key => $bundle_id,
      ];

      if (!$entity = $entity_storage->loadByProperties($load_properties)) {
        $entity_values = [];
        $entity_values['machine_name'] = $machine_name;
        if (!empty($bundle_key)) {
          $entity_values[$bundle_key] = $bundle_id;
        }
        $entity = $entity_storage->create($entity_values);
      }
      else {
        $entity = reset($entity);
      }

      $this->entityBuilder->fromEntity($entity);

      foreach ($entity_definition as $field_name => $value) {

        if (in_array($field_name, [$bundle_key])) {
          continue;
        }

        if (!$entity->hasField($field_name)) {
          $this->logger->warning("Entity type @entity_type_id / entity ID @entity_id / bundle @bundle is missing field @field_name at @place.", [
            '@entity_type_id' => $entity_type_id,
            '@entity_id' => $entity->id() ?: 'new',
            '@bundle' => $entity->bundle(),
            '@field_name' => $field_name,
          ]);
          continue;
        }

        /** @var \Drupal\Core\Field\FieldItemListInterface $field */
        $field = $entity->get($field_name);
        $field_type = $field->getFieldDefinition()->getType();

        if (is_array($value)) {
          foreach ($value as $delta => $subvalue) {
            if ($field_type == 'entity_reference') {
              $this->entityBuilder->setReferenceByLabel($field_name, $subvalue, $delta);
            }
            else {
              $this->entityBuilder->set($field_name, $subvalue, $delta);
            }
          }
        }
        else {
          if ($field_type == 'entity_reference') {
            $this->entityBuilder->setReferenceByLabel($field_name, $value);
          }
          else {
            $this->entityBuilder->set($field_name, $value);
          }
        }

      }

      $this->entityBuilder->save();

    }

  }

  /**
   *
   */
  public function cloneBundleEntities($entity_type_id, $source_bundle, $target_bundle) {

    $entity_type = $this->entityHelper->getDefinition($entity_type_id);
    $entity_storage = $this->entityHelper->getStorage($entity_type_id);

    $bundle_key = $entity_type->getKey('bundle');

    $entities_source = $entity_storage->loadByProperties([
      $bundle_key => $source_bundle,
    ]);

    foreach ($entities_source as $entity_source) {

      $entity_target = $entity_source->createDuplicate();
      $entity_target->set($bundle_key, $target_bundle);
      $entity_target->save();

    }

  }

}
