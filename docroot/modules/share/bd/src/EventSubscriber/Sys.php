<?php

namespace Drupal\bd\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Changes the response to a redirect, if a redirect rules action was executed .
 */
class Sys implements EventSubscriberInterface {

  /**
   * Runs on kernel init.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event.
   */
  public function onRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
  }

  /**
   * Runs on kernel exit.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   */
  public function onResponse(FilterResponseEvent $event) {
    $request = $event->getRequest();
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

}
