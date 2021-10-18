<?php

namespace Drupal\bd\EventSubscriber;

use Drupal\bd\Event\KernelFinishRequest;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Drupal\bd\Event\KernelResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * The generic event subscriber.
 */
class Generic implements EventSubscriberInterface {

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The cron configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new Kernel.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity type helper.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key-value store service.
   */
  public function __construct(EntityHelper $entity_helper, ConfigFactoryInterface $config_factory, StateInterface $state) {
    $this->entityHelper = $entity_helper;
    $this->config = $config_factory->getEditable('system.site');
    $this->state = $state;
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function onRequest(GetResponseEvent $event) {
    $d = 1;
  }

  /**
   * Runs on request terminal.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The Event to process.
   */
  public function onTerminate(PostResponseEvent $event) {
  }

  /**
   * React to response event.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event.
   */
  public function onResponse(FilterResponseEvent $event) {

    // Needs to run before rules redirect event subscriber in order for
    // redirects to work.
    $event_rules = new KernelResponse();
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch(KernelResponse::EVENT_NAME, $event_rules);

  }

  /**
   * React to response event.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event.
   */
  public function onFinishRequest(FinishRequestEvent $event) {

    // Needs to run before rules redirect event subscriber in order for
    // redirects to work.
    $event_rules = new KernelFinishRequest();
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch(KernelFinishRequest::EVENT_NAME, $event_rules);

  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => [['onRequest', 100]],
      KernelEvents::TERMINATE => [['onTerminate', 100]],
      KernelEvents::RESPONSE => [['onResponse', 100]],
      KernelEvents::FINISH_REQUEST => [['onFinishRequest', 100]],
    ];
  }

}
