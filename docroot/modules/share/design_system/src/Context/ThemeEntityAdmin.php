<?php

namespace Drupal\design_system\Context;

use Drupal\bd\Context\Base;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;

/**
 * Sets the admin theme entity.
 */
class ThemeEntityAdmin extends Base {

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {

    $context_definition = EntityContextDefinition::fromEntityTypeId('theme_entity')
      ->setRequired(FALSE);

    $cacheability = new CacheableMetadata();

    $entity_theme = $this->entityTypeManager->getStorage('theme_entity')->load(21);

    $context = new Context($context_definition, $entity_theme);
    $context->addCacheableDependency($cacheability);

    return ['theme_entity_admin' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return ['theme_entity_admin' => EntityContext::fromEntityTypeId('theme_entity', $this->t('Admin theme entity'))];
  }

}
