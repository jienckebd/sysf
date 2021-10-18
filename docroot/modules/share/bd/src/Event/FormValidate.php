<?php

namespace Drupal\bd\Event;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Rules event.
 */
class FormValidate extends Event {

  const EVENT_NAME = 'bd.rules.form.validate';

  /**
   * The form ID.
   *
   * @var string
   */
  public $form_id;

  /**
   * The form structure.
   *
   * @var array
   */
  public $form;

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  public $form_state;

  /**
   * FormValidate constructor.
   *
   * @param string $form_id
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function __construct(string $form_id, array &$form, FormStateInterface $form_state) {
    $this->form_id = $form_id;
    $this->form = $form;
    $this->form_state = $form_state;
  }

}
