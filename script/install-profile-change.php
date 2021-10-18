<?php

$old = \Drupal::installProfile();

$new = 'minimal';

$extension_config = \Drupal::configFactory()->getEditable('core.extension');
$extension_config->set('profile', $new)
  ->save();

drupal_flush_all_caches();

\Drupal::service('module_installer')->install([$new]);
\Drupal::service('module_installer')->uninstall([$old]);

$schema = \Drupal::keyValue('system.schema');
$weight = 8000;
if ($weight == $schema->get($old)) {
  $schema->delete($old);
}
$schema->set($new, $weight);

drupal_flush_all_caches();

$db = \Drupal::database();
$db->delete('key_value')
  ->condition('collection', 'state')
  ->condition('name', 'system.profile.files')
  ->execute();

drupal_flush_all_caches();

$module_data = \Drupal::config('core.extension')->get('module');
unset($module_data[$old]);
$extensions = \Drupal::configFactory()->getEditable('core.extension');
$extensions->set('module', $module_data)->save();
