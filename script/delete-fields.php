<?php

$field_config = \Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties([
  'entityType' => 'block_content',
  'type' => 'entity_reference',
]);

/**
 * @var string $entity_id
 * @var \Drupal\field\FieldConfigInterface $field_config
 */
foreach ($field_config as $entity_id => $field_config) {

  if (!$bd_config = $field_config->getThirdPartySettings('bd')) {
    continue;
  }

  if (empty($bd_config['dom']['subattribute'])) {
    continue;
  }

  $value = $bd_config['dom']['subattribute'];
  unset($bd_config['dom']['subattribute']);
  $bd_config['behavior']['dom']['subattribute'] = $value;
  $field_config->setThirdPartySetting('bd', 'behavior', $bd_config['behavior']);
  $field_config->save();

}
