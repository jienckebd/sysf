<?php

namespace Drupal\bd\Entity;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;

/**
 * Provides index manager.
 */
class EntityIndex {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   * */
  public $entityHelper;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  public $database;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  public $state;

  /**
   * The key value service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  public $keyValue;

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
   * Manager constructor.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   */
  public function __construct(
    EntityHelper $entity_helper,
    Connection $database,
    ConfigFactoryInterface $config_factory,
    StateInterface $state,
    KeyValueFactoryInterface $key_value,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->keyValue = $key_value;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex($index_id) {

    // @todo add cache contexts.
    $theme_id = \Drupal::theme()->getActiveTheme()->getName();

    $cid = "entity.index.{$index_id}.{$theme_id}";

    if ($index = $this->cache->get($cid)) {
      $index = $index->data;
    }
    else {

      $index = [];

      if (empty($index['ief_attach'])) {
        $index['ief_attach'] = \Drupal::configFactory()->getConfig('bd.ief_attach');

        $theme_handler = \Drupal::service('theme_handler');

        $active_theme_dom_ids = [];
        if (!$entity_theme_active = $theme_handler->getActiveThemeEntity()) {
          return FALSE;
        }

        // Get referenced entity by entity type ID.
        /** @var \Drupal\bd\Entity\EntityRelation $relation */
        $relation = \Drupal::service('entity.relation');
        if (!$index['theme_dom_ids'] = $relation->getReferencedByBundle($entity_theme_active, 'dom', NULL, 5)) {
          return FALSE;
        }

        $dom_mapping_result = $this->database
          ->select("dom__aid", 'da')
          ->condition('entity_id', $index['theme_dom_ids'], 'IN')
          ->fields('da')
          ->execute()
          ->fetchAll();

        $dom_mapping = [];
        if (!empty($dom_mapping_result)) {

          $dom_collection_mapping_result = $this->database
            ->select("dom__dom", 'dd')
            ->condition('entity_id', $index['theme_dom_ids'], 'IN')
            ->fields('dd')
            ->execute()
            ->fetchAll();

          foreach ($dom_mapping_result as $result) {

            if ($result->deleted) {
              continue;
            }

            $dom_mapping_item = [];
            $aid = $result->aid_value;
            $dom_mapping_item['aid'] = $aid;

            // Attach ief_attach config.
            if ($ief_attach_config = NestedArray::getValue($index['ief_attach'], explode('.', $aid))) {
              $dom_mapping_item['ief_attach_config'] = $ief_attach_config;
            }

            $dom_mapping[$result->entity_id] = $dom_mapping_item;
          }

          // Attach contained DOM entities.
          if (!empty($dom_collection_mapping_result)) {
            foreach ($dom_collection_mapping_result as $contained_result) {

              $dom_collection_id = $contained_result->entity_id;
              $dom_contained_id = $contained_result->dom_target_id;
              $dom_mapping[$dom_collection_id]['contained'][] = $dom_contained_id;

            }
          }
        }

        // Get fields referencing style bundle / dom entity type.

        /** @var \Drupal\field\FieldConfigInterface[] $entity_field_config_list */
        if ($entity_field_config_list = $this->entityHelper->getStorage('field_config')
          ->loadMultiple()) {
          foreach ($entity_field_config_list as $entity_id => $field_config) {

            if (!$config = $field_config->getThirdPartySetting('bd', 'dom')) {
              continue;
            }

            if (empty($config['theme_hook'])) {
              continue;
            }

            $target_entity_type_id = $field_config->getTargetEntityTypeId();
            $field_name = $field_config->getName();

            // Get theme hook and selector from field_config 3rd party settings.
            $theme_hooks = explode(',', $config['theme_hook']);
            $selector_raw = $config['theme_hook_selector'];

            // Get field values with database query.
            $table_name = "{$target_entity_type_id}__{$field_name}";
            $target_field_name = "{$field_name}_target_id";

            $field_results = $this->database
              ->select($table_name, 'dd')
              ->condition($target_field_name, $index['theme_dom_ids'], 'IN')
              ->fields('dd')
              ->execute()
              ->fetchAll();

            // Attach to DOM mapping.
            if (!empty($field_results)) {
              foreach ($field_results as $result) {

                $region = $this->entityHelper->getStorage('dom')
                  ->load($result->entity_id);

                $sql_field_name = "{$field_name}_target_id";
                $dom_entity_id = $result->{$sql_field_name};

                $replacements = [];

                if ($region->hasField('field_default')) {
                  if ($region->field_default->value) {
                    $replacements['{{ region_id }}'] = 'content';
                  }
                  else {
                    $replacements['{{ region_id }}'] = "region{$result->entity_id}";
                  }
                }
                else {
                  $replacements['{{ region_id }}'] = "region{$result->entity_id}";
                }

                $selector = str_replace(array_keys($replacements), array_values($replacements), $selector_raw);

                $dom_mapping_item = [];

                $dom_mapping_item['ief_attach_config']['theme_hook'] = $theme_hooks;
                $dom_mapping_item['ief_attach_config']['selector'] = $selector;
                $dom_mapping_item['contained'] = [$dom_entity_id];

                $dom_mapping[$result->entity_id][] = $dom_mapping_item;

              }
            }

          }
        }

        $index['dom_mapping'] = $dom_mapping;

      }

      $this->cache->set($cid, $index, Cache::PERMANENT, ['entity_index', "entity_index:{$index_id}"]);
    }

    return $index;
  }

}
