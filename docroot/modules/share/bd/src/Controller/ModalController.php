<?php

namespace Drupal\bd\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * ModalFormExampleController class.
 */
class ModalController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Callback for opening the modal form.
   */
  public function entityForm($entity_type, $bundle, $context_entity_type = NULL, $context_entity_id = NULL) {
    $response = new AjaxResponse();

    $entity_helper = \Drupal::service('entity.helper');

    $entity_definition = $entity_helper->getDefinition($entity_type);

    $bundle_key = $entity_definition->getKey('bundle');

    $entity = $entity_helper->getStorage($entity_type)->create([
      $bundle_key => $bundle,
    ]);

    $modal_title = t('Post @type', [
      '@type' => $bundle,
    ]);

    $entity_form = \Drupal::service('entity.form_builder')->getForm($entity, 'modal');

    $options = [
      'dialogClass' => 'ui-dialog ui-dialog-buttonpane modal-lg',
      'width' => '1200',
      'height' => '800',

    ];
    $response->addCommand(new OpenModalDialogCommand($modal_title, $entity_form, $options));
    $response->addCommand(new InvokeCommand('body', 'removeClass', ['ajax--active']));
    return $response;
  }

  /**
   * Callback for opening the modal form.
   */
  public function closeModal() {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
