<?php

namespace Drupal\bd\Config;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\TypedConfigManagerInterface as Base;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Extends core typed config manager interface.
 */
interface TypedConfigManagerInterface extends Base {

  /**
   * Config keys added to all configs.
   */
  const CONFIG_KEY_DEFAULT = [
    'uuid',
    'langcode',
    'status',
    'dependencies',
    'third_party_settings',
    '_core',
  ];

  /**
   * Maps data types to form widget types. Otherwise, is applicable is checked.
   *
   * @var array
   */
  const MAP_FORCE_DATA_TYPE_WIDGET_TYPE = [
    'label' => 'text_input',
    'text' => 'textarea',
    'description' => 'textarea',
  ];

  /**
   * @param $data_type
   * @param $config_schema
   * @param $property_name
   * @param $property_value
   * @param null $parent
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface|object
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function createTypedDataDefinition($data_type, $config_schema, $property_name, $property_value, $parent = NULL);

  /**
   * @param \Drupal\Core\TypedData\TypedDataInterface $typed_data_definition
   * @param array $plugin_config
   *
   * @return bool|\Drupal\typed_data\Widget\FormWidgetInterface
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getFormWidgetForTypedDataDefinition(TypedDataInterface $typed_data_definition, array $plugin_config = []);

  /**
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *
   * @return \Symfony\Component\Validator\ConstraintViolationList
   */
  public function validateConfigEntity(ConfigEntityInterface $entity);

  /**
   * @param $config_schema_id
   * @param $config_data
   * @param null $parent
   *
   * @return \Symfony\Component\Validator\ConstraintViolationList
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function validateConfigSchema($config_schema_id, $config_data, EntityInterface $entity = NULL);

  /**
   * @param $plugin_type_id
   * @param $plugin_id
   * @param array $plugin_config
   *
   * @return mixed
   */
  public function createPluginInstance($plugin_type_id, $plugin_id, array $plugin_config = []);

}
