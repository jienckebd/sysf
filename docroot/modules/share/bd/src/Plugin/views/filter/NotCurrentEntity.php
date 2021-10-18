<?php

namespace Drupal\bd\Plugin\views\filter;

use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter to show only the latest revision of an entity.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("not_current_entity")
 */
class NotCurrentEntity extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type helper service.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * Views Handler Plugin Manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinHandler;

  /**
   * Constructs a new LatestRevision.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   entity type helper Service.
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_handler
   *   Views Handler Plugin Manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityHelper $entity_helper, ViewsHandlerManager $join_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityHelper = $entity_helper;
    $this->joinHandler = $join_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity.helper'),
      $container->get('plugin.manager.views.join')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    $query_base_table = $this->relationship ?: $this->view->storage->get('base_table');

    $entity_type = $this->entityHelper->getDefinition($this->getEntityType());
    $keys = $entity_type->getKeys();

    $entity = \Drupal::routeMatch()->getParameter('node');
    if (!empty($entity)) {
      $entity_id = $entity->id();
      $query->addWhere($this->options['group'], "$query_base_table.{$keys['id']}", $entity_id, '!=');
      // $this->query->addWhere(0, "$this->tableAlias.{$keys['id']}", $entity_id, '!=');.
    }
  }

}
