<?php

namespace Drupal\bd\DependencyInjection;

use Drupal\Core\DependencyInjection\ServiceProviderBase as Base;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Extends core base service provider base.
 */
abstract class ServiceProviderBase extends Base implements ServiceProviderInterface {

  /**
   * The service overrides.
   *
   * @var array
   */
  const SERVICE_OVERRIDE = [];

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {

    if (!empty(static::SERVICE_OVERRIDE['alter'])) {
      foreach (static::SERVICE_OVERRIDE['alter'] as $service_id => $service_override) {
        if ($container->has($service_id)) {

          if ($service_override === FALSE) {
            $container->removeDefinition($service_id);
            continue;
          }

          $definition = $container->getDefinition($service_id);
          $definition->setClass($service_override['class']);

          // If defined, process new services to inject.
          if (!empty($service_override['reference']['add'])) {
            foreach ($service_override['reference']['add'] as $new_reference) {
              $definition->addArgument(new Reference($new_reference));
            }
          }

        }
      }
    }

    $decorator = [];

    foreach ($container->getServiceIds() as $service_id) {

      if (stripos($service_id, 'plugin.manager') !== FALSE) {
        $decorator['plugin'][] = $service_id;
      }

    }
    return;
    foreach ($decorator as $decorator_type => $service_ids) {
      foreach ($service_ids as $service_id) {

        $decorator_service_id = "decorator.{$decorator_type}.{$service_id}";
        $decorator_inner_service_id = "{$decorator_service_id}.inner";

        $container->register($decorator_service_id, 'Drupal\bd\PluginManager\Decorator')
          ->addArgument(new Reference($decorator_inner_service_id))
          ->setDecoratedService($service_id)
          ->setProperty('_serviceId', $service_id)
          ->addTag('decorator', [
            'type' => $decorator_type,
          ]);

      }
    }

    /**
     * webprofiler.debug.asset.css.collection_renderer:
     * class: Drupal\webprofiler\Asset\CssCollectionRendererWrapper
     * public: false
     * decorates: asset.css.collection_renderer
     * arguments: ['@webprofiler.debug.asset.css.collection_renderer.inner', '@webprofiler.assets']
     * properties:
     * _serviceId: 'asset.css.collection_renderer'
     */

  }

}
