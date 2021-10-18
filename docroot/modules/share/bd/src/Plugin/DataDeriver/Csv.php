<?php

namespace Drupal\bd\Plugin\DataDeriver;

use Drupal\bd\Plugin\EntityPluginBase;
use Drupal\bd\Component\Csv\Csv as CsvComponent;

/**
 * Provides a date data type.
 *
 * @DataDeriver(
 *   plugin_type = "data_deriver",
 *   id = "csv",
 *   label = @Translation("CSV"),
 *   description = @Translation("Provides a CSV data type.")
 * )
 */
class Csv extends EntityPluginBase {

  /**
   * @param array $data
   *
   * @return array|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function process(array &$data = []) {
    $derived = [];

    $deriver_definition = $this->configuration;

    if (!empty($deriver_definition['entity_type_id'])) {
      $entity_type_id = $deriver_definition['entity_type_id'];
      $entity_id = $deriver_definition['entity_id'];
      $field_name = $deriver_definition['field_name'];

      $entity_helper = \Drupal::service('entity.helper');

      $entity_storage = $entity_helper->getStorage($entity_type_id);

      if (!$entity = $entity_storage->load($entity_id)) {
        return FALSE;
      }

      if (!$entity->hasField($field_name)) {
        return FALSE;
      }

      $field = $entity->get($field_name);

      if ($field->isEmpty()) {
        return FALSE;
      }

      /** @var \Drupal\file\FileInterface $entity_file */
      $entity_file = $field->entity;

      // Load file.
      $file_path = $entity_file->getFileUri();

    }
    elseif (!empty($this->configuration['csv_file_path'])) {
      $file_path = $this->configuration['csv_file_path'];
    }
    else {
      throw new \Exception("Invalid plugin configuration.");
    }

    /** @var \Drupal\bd\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    if (!$file_data = $file_system->loadFile($file_path)) {
      return FALSE;
    }

    $derived = CsvComponent::toAssoc($file_path);

    // Sanitize the data.
    foreach ($derived as $key => &$value) {

      if (isset($value['machine_name'])) {
        // $value['machine_name'] = Str::sanitizeMachineName($value['machine_name']);
      }

    }

    // @todo needs to run before CSV to allow overwrite from plugin.
    if (!empty($this->configuration['base_definition'])) {
      foreach ($this->configuration['base_definition'] as $key => $child) {
        foreach ($derived as $derived_id => &$derived_value) {
          $derived_value[$key] = $child;
        }
      }
    }

    return $derived;
  }

}
