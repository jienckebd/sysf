<?php

namespace Drupal\design_system\Ajax\Traits;

use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Symfony\Component\HttpFoundation\Request;
use Drupal\design_system\Ajax\Command\FormSubmitPassCommand;
use Drupal\design_system\Ajax\Command\FormSubmitFailCommand;
use Drupal\design_system\Ajax\Command\ScrollCommand;
use Drupal\Core\Url;

/**
 * Ajax helper for forms.
 */
trait Form {

  /**
   * Attach ajax to a form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function attachConfigAjax(array &$form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'form--ajax';

    $form_id = $form_state->getBuildInfo()['form_id'];
    $ajax_id = "ajax--wrapper--{$form_id}";
    $ajax_wrapper = Html::cleanCssIdentifier($ajax_id);

    $form['#ajax_wrapper'] = $ajax_wrapper;
    $form['#prefix'] = '<div id="' . $ajax_wrapper . '" class="ajax--wrapper">';
    $form['#suffix'] = '</div>';
  }

  /**
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface|null $form_state
   * @param array $complete_form
   */
  public function attachAjaxToElement(array &$element, FormStateInterface $form_state = NULL, array &$complete_form = NULL) {
    $element['#ajax'] = [
      'callback' => [static::class, 'ajaxOpSubmit'],
      'wrapper' => $complete_form['#ajax_wrapper'],
      'event' => 'click',
    ];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\Core\Ajax\AjaxResponse|null $response
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function ajaxOpSubmit(array &$form, FormStateInterface $form_state, Request $request, AjaxResponse $response = NULL) {

    /** @var \Drupal\bd\Entity\EntityHelper $entity_helper */
    $entity_helper = \Drupal::service('entity.helper');
    $messenger = \Drupal::messenger();
    $route_match = \Drupal::routeMatch();

    /** @var \Drupal\Core\Form\FormInterface $form_object */
    $form_object = $form_state->getFormObject();

    $triggering_element = $form_state->getTriggeringElement();

    $button_config = !empty($triggering_element['#config']) ? $triggering_element['#config'] : [];
    $t_context = [];

    $route_name = $route_match->getRouteName();
    $route_name_disable_redirect = [
      'entity_browser.edit_form',
    ];

    $ajax_config = !empty($button_config['ajax']) ? $button_config['ajax'] : [
      'refresh' => [
        'plugin' => 'refresh_form',
      ],
    ];

    // Check for redirect in query first.
    $redirect_target = NULL;
    $query = $request->query;
    if ($query->has('destination')) {
      $redirect_target = $query->get('destination');
    }
    elseif (!$form_state->get('disable_redirect_strict')) {
      // Fall back to checking form state.
      $form_state->disableRedirect(FALSE);
      if ($redirect_target_url = $form_state->getRedirect()) {
        if ($redirect_target_url instanceof Url) {
          $redirect_target = $redirect_target_url->toString();
        }
      }
    }

    $form_wrapper = '#' . $form['#ajax_wrapper'];
    $form_dom_id = $form['#id'];
    $form_id = $form_dom_id;
    $new_wrapper_id = !empty($form['#new_wrapper_id']) ? $form['#new_wrapper_id'] : Html::getUniqueId('ajax--wrapper');
    $response = new AjaxResponse();

    if ($form_object instanceof EntityFormInterface) {

      $entity = $form_object->getEntity();
      $entity_type = $entity->getEntityType();
      $t_context = $entity_helper->getTContext($entity_type, $entity);

      if ($ajax_replace = $request->query->get('ajax-replace')) {
        if ($ajax_replace == 'entity') {

          $entity_type_id = $request->query->get('entity-type');
          $entity_id = $request->query->get('entity-id');
          $view_mode_id = $request->query->get('view-mode');

          // @todo figure out specific item in cache needs to be invalidated.
          // \Drupal::cache('data')->deleteAll();
          // \Drupal::cache('render')->deleteAll();
          $build = $entity_helper->getViewBuilder($entity_type_id)->view($entity, $view_mode_id);
          $replace_selector = "[data-entity-type=\"{$entity_type_id}\"][data-entity-id=\"{$entity_id}\"][data-view-mode=\"{$view_mode_id}\"]";
          $response->addCommand(new ReplaceCommand($replace_selector, $build));
          $response->addCommand(new CloseDialogCommand('#entity-edit'));
          $response->addCommand(new InvokeCommand('.ajax--processing', 'removeClass', ['ajax--processing']));
          $response->addCommand(new InvokeCommand($replace_selector, 'effect', ['highlight', 2500]));
          return $response;

        }
      }

      $entity_type = $entity->getEntityType();
      $entity_type_id = $entity_type->id();

    }

    // If any errors, prepare a FormSubmitFailCommand.
    if ($form_state->getErrors()) {
      $response->addCommand(new FormSubmitFailCommand($form_id, $form_dom_id, $new_wrapper_id));

      if (!empty($button_config['message']['fail']['clear'])) {
        $messenger->deleteAll();
      }

      if (!empty($button_config['message']['fail']['list'])) {
        foreach ($button_config['message']['fail']['list'] as $delta => $message) {
          $messenger->addError(t($message, $t_context));
        }
      }

      $form['messages'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ];

      $response->addCommand(new ReplaceCommand($form_wrapper, $form));
      $response->addCommand(new ScrollCommand($form_wrapper, '-50'));

      return $response;
    }

    if ($form_object instanceof EntityFormInterface) {
      // If no redirect provided in query params, base it on entity.  Must run
      // after form state errors check.
      if ($entity instanceof ContentEntityInterface && empty($redirect_target)) {
        if ($entity->hasField('layout_builder__layout')) {
          // Redirect to layout if has field from layout_builder.
          $redirect_target = $entity->toUrl('canonical');
          $internal_path = $redirect_target->getInternalPath();
          $redirect_target = "/{$internal_path}/layout";
        }
        elseif (TRUE) {
          // Redirect to canonical if not admin entity type.
          $redirect_target = $entity->toUrl('canonical')->toString();
        }
        else {
          // Redirect to collection if admin entity type.
          $redirect_target = $entity->toUrl('collection')->toString();
        }

      }
    }

    $response->addCommand(new FormSubmitPassCommand($form_id, $form_dom_id, $new_wrapper_id));

    if (!empty($button_config['message']['pass']['clear'])) {
      $messenger->deleteAll();
    }

    if (!empty($button_config['message']['pass']['list'])) {
      foreach ($button_config['message']['pass']['list'] as $delta => $message) {
        $messenger->addStatus(t($message, $t_context));
      }
    }

    // Don't redirect for entity browsers.
    if (in_array($route_name, $route_name_disable_redirect)) {
      $redirect_target = NULL;
    }

    if (!empty($redirect_target)) {
      $response->addCommand(new RedirectCommand($redirect_target));
    }
    else {

      foreach ($ajax_config as $step_id => $ajax_config_step) {

        $plugin_id = $ajax_config_step['plugin'];

        switch ($plugin_id) {

          case 'refresh_form':

            // If update to existing entity and no destination is set, reload the
            // form.
            $form['messages'] = [
              '#type' => 'status_messages',
              '#weight' => -1000,
            ];

            /** @var \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder */
            $entity_form_builder = \Drupal::service('entity.form_builder');
            $entity_form_op = $form_object->getOperation();
            // $form = $entity_form_builder->getForm($entity, $entity_form_op);
            $response->addCommand(new ReplaceCommand($form_wrapper, $form));
            $response->addCommand(new ScrollCommand($form_wrapper, '-50'));

            break;

          case 'refresh_route':

            $current_url = \Drupal::request()->getPathInfo();
            $redirect_target = "{$current_url}?_wrapper_format=drupal_ajax_history";
            $response->addCommand(new RedirectCommand($current_url));

            // $response = new RedirectResponse($redirect_target);
            break;

          case 'redirect':

            if (empty($redirect_target)) {
              if (!empty($ajax_config_step['definition']['rel'])) {
                $redirect_target = $entity->toUrl($ajax_config_step['definition']['rel'])->toString();
              }
            }

            if (!empty($redirect_target)) {
              $response->addCommand(new RedirectCommand($redirect_target));
            }

            break;

        }

      }

    }

    return $response;
  }

  /**
   *
   */
  protected static function processEntityFormAjaxOpSubmit() {

  }

}
