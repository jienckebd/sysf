<?php

namespace Drupal\design_system\Ajax\Command;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for submitting a form.
 *
 * @ingroup ajax
 */
class FormSubmitPassCommand extends FormSubmitBaseCommand implements CommandInterface {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'formSubmitPass',
      'formId' => $this->formId,
      'formDomId' => $this->formDomId,
      'newWrapper' => $this->newWrapper,
      'settings' => $this->settings,
    ];
  }

}
