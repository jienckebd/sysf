<?php

namespace Drupal\design_system\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\bd\Entity\EntityHelper;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\design_system\DesignSystem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\dropzonejs\Events\Events;
use Drupal\dropzonejs\Events\DropzoneMediaEntityCreateEvent;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\design_system\MediaHelper;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Class Generic.
 */
class Generic implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type helper.
   *
   * @var \Drupal\bd\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * The design system.
   *
   * @var \Drupal\design_system\DesignSystem
   */
  protected $designSystem;

  /**
   * The media helper.
   *
   * @var \Drupal\design_system\MediaHelper
   */
  protected $mediaHelper;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The default cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a Generic object.
   *
   * @param \Drupal\bd\Entity\EntityHelper $entity_helper
   *   The entity storage for views.
   * @param \Drupal\design_system\DesignSystem $design_system
   *   The design system.
   * @param \Drupal\design_system\MediaHelper $media_helper
   *   The media helper.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityHelper $entity_helper,
    DesignSystem $design_system,
    MediaHelper $media_helper,
    RouteMatchInterface $route_match,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger
  ) {
    $this->entityHelper = $entity_helper;
    $this->designSystem = $design_system;
    $this->mediaHelper = $media_helper;
    $this->routeMatch = $route_match;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = ['onBuildRender'];
    $events[KernelEvents::RESPONSE] = ['onResponse'];
    $events[Events::MEDIA_ENTITY_PRECREATE] = ['onDropzoneJsMediaEntityPreCreate'];
    return $events;
  }

  /**
   * Adds block classes to section component.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event) {
  }

  /**
   * React to response event.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event.
   */
  public function onResponse(FilterResponseEvent $event) {

    $route = $this->routeMatch->getRouteObject();
    $route_name = $this->routeMatch->getRouteName();

    $route_names_fw_dark = [
      'entity_embed.dialog',
    ];

    $route_names_dark = [
      'layout_builder.remove_section',
      'layout_builder.choose_section',
      'layout_builder.move_sections_form',
      'layout_builder.choose_block',
      'layout_builder.choose_inline_block',
      'layout_builder.move_block_form',
      'layout_builder.remove_block',
      'layout_builder.move_block',
    ];

    $route_names_transparent = [
      'layout_builder.add_component',
      'layout_builder.add_section',
      'layout_builder.configure_section',
      'layout_builder.add_block',
      'layout_builder.update_block',
    ];

    $height_off_canvas = '340';

    $response = $event->getResponse();
    if ($response instanceof AjaxResponse) {

      $commands = &$response->getCommands();

      foreach ($commands as $key => &$command) {

        $add_fade = FALSE;

        if (isset($command['method'])) {
          if ($command['method'] == 'replaceWith' || $command['method'] == 'html') {
            $add_fade = TRUE;
          }
        }

        if ($add_fade) {
          // $command['effect'] = 'fade';
          //          $command['settings']['speed'] = 5000;
        }

      }

    }

  }

  /**
   * React to dropzonejs media entity creation.
   *
   * @param \Drupal\dropzonejs\Events\DropzoneMediaEntityCreateEvent $event
   *   The dropzonejs event.
   */
  public function onDropzoneJsMediaEntityPreCreate(DropzoneMediaEntityCreateEvent $event) {

    $entity_media = $event->getMediaEntity();
    $file = $event->getFile();
    $mime_type = $file->getMimeType();

    $entity_storage_media = $this->entityHelper->getStorage('media');

    $media_type_id = $this->mediaHelper->getMediaTypeFromMime($mime_type);

    /** @var \Drupal\media\MediaTypeInterface $entity_media_type */
    $entity_media_type = $this->entityHelper->getStorage('media_type')->load($media_type_id);

    $source_field_name = $entity_media_type->getSource()->getConfiguration()['source_field'];

    $entity_media = $entity_storage_media->create([
      'bundle' => $media_type_id,
    ]);

    $entity_media->set($source_field_name, $file->id());

    $event->setMediaEntity($entity_media);

  }

}
