<?php

namespace Drupal\bd\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\address\Event\AddressEvents;

/**
 *
 */
class Address implements EventSubscriberInterface {

  /**
   *
   */
  public static function getSubscribedEvents() {
    $events[AddressEvents::ADDRESS_FORMAT][] = ['onGetDefinition', 0];
    return $events;
  }

  /**
   *
   */
  public function onGetDefinition($event) {
    $definition = $event->getDefinition();
    // This makes city (locality) field required and leaves
    // the rest address fields as optional.
    $definition['required_fields'] = [];
    $event->setDefinition($definition);
  }

}
