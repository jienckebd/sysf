<?php

namespace Drupal\autoref\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Entity\Controller\EntityViewController as Base;

/**
 * Defines a controller to render a single entity.
 */
class EntityViewController extends Base {

  /**
   * Provides a page to render a single entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   The Entity to be rendered. Note this variable is named $_entity rather
   *   than $entity to prevent collisions with other named placeholders in the
   *   route.
   * @param string $view_mode
   *   (optional) The view mode that should be used to display the entity.
   *   Defaults to 'full'.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function view(EntityInterface $_entity, $view_mode = 'full') {
    $page = $this->entityManager
      ->getViewBuilder($_entity->getEntityTypeId())
      ->view($_entity, $view_mode);

    $page['#pre_render'][] = [$this, 'buildTitle'];
    $page['#entity_type'] = $_entity->getEntityTypeId();
    $page['#' . $page['#entity_type']] = $_entity;

    $request = \Drupal::request();
    $is_ajax = $request->isXmlHttpRequest();

    if ($is_ajax) {
      $response = new AjaxResponse();
      $title = $_entity->label();

      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $response->setAttachments($form['#attached']);

      $options = [
        'dialogClass' => 'modal-sm',
      ];

      $response->addCommand(new OpenModalDialogCommand($title, $page, $options));

      return $response;
    }

    return $page;
  }

  /**
   * Provides a page to render a single entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   The Entity to be rendered. Note this variable is named $_entity rather
   *   than $entity to prevent collisions with other named placeholders in the
   *   route.
   * @param string $view_mode
   *   (optional) The view mode that should be used to display the entity.
   *   Defaults to 'full'.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function viewDynamicEntity(EntityInterface $_entity, $view_mode = 'full') {
    $page = $this->entityManager
      ->getViewBuilder($_entity->getEntityTypeId())
      ->view($_entity, $view_mode);

    $page['#pre_render'][] = [$this, 'buildTitle'];
    $page['#entity_type'] = $_entity->getEntityTypeId();
    $page['#' . $page['#entity_type']] = $_entity;

    return $page;
  }

}
