<?php

namespace Drupal\design_system\Ajax\Command;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for submitting a form.
 *
 * @ingroup ajax
 */
abstract class FormSubmitBaseCommand implements CommandInterface {

  /**
   * The form ID.
   *
   * @var string
   */
  protected $formId;

  /**
   * The form ID.
   *
   * @var string
   */
  protected $formDomId;

  /**
   * The form ID.
   *
   * @var string
   */
  protected $newWrapper;

  /**
   * Any required settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * FormSubmitCommand constructor.
   *
   * @param $form_id
   * @param $form_dom_id
   * @param $new_wrapper
   * @param array $settings
   */
  public function __construct($form_id, $form_dom_id, $new_wrapper, array $settings = []) {
    $this->formId = $form_id;
    $this->formDomId = $form_dom_id;
    $this->newWrapper = $new_wrapper;
    $this->settings = $settings;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'formSubmit',
      'formId' => $this->formId,
      'formDomId' => $this->formDomId,
      'newWrapper' => $this->newWrapper,
      'settings' => $this->settings,
    ];
  }

}
