<?php

namespace Drupal\design_system\Context;

use Drupal\bd\Context\Base;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;

/**
 * Sets the default theme entity.
 */
class ThemeEntityDefault extends Base {

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {

    $context_definition = EntityContextDefinition::fromEntityTypeId('theme_entity')
      ->setRequired(FALSE);

    $cacheability = new CacheableMetadata();

    $entity_theme = $this->entityTypeManager->getStorage('theme_entity')->load(14);

    $context = new Context($context_definition, $entity_theme);
    $context->addCacheableDependency($cacheability);

    return ['theme_entity_default' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return ['theme_entity_default' => EntityContext::fromEntityTypeId('theme_entity', $this->t('Default theme entity'))];
  }

}
