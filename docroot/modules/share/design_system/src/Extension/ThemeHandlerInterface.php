<?php

namespace Drupal\design_system\Extension;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ThemeHandlerInterface as Base;

/**
 * Extends core theme handler.
 */
interface ThemeHandlerInterface extends Base {

  /**
   * The entity type ID for themes.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_THEME = 'theme_entity';

  /**
   * The entity type ID for DOM components.
   *
   * @var string
   */
  const ENTITY_TYPE_ID_DOM = 'dom';

  /**
   * Field name that stores color palette reference.
   *
   * @var string
   */
  const FIELD_NAME_COLOR_PALETTE = 'field_color_scheme';

  /**
   * Field name that stores the layout reference.
   *
   * @var string
   */
  const FIELD_NAME_LAYOUT = 'field_layout';

  /**
   * Field name that stores the region references from layout.
   *
   * @var string
   */
  const FIELD_NAME_REGION = 'field_region';

  /**
   * Field name that stores the breakpoint references from theme.
   *
   * @var string
   */
  const FIELD_NAME_BREAKPOINT = 'field_breakpoint';

  /**
   * Field name that stores the DOM tag references from theme.
   *
   * @var string
   */
  const FIELD_NAME_DOM_TAG = 'field_dom_tag';

  /**
   * Field name that stores the component references from theme.
   *
   * @var string
   */
  const FIELD_NAME_COMPONENT = 'field_region';

  /**
   * @return array|\Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUiConfig();

  /**
   * @return array|mixed|null
   */
  public function getActiveThemeName();

  /**
   * @return array|mixed|null
   */
  public function getDefaultThemeName();

  /**
   * @return array|mixed|null
   */
  public function getAdminThemeName();

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getActiveThemeEntity();

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAdminThemeEntity();

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDefaultThemeEntity();

  /**
   * @return bool
   */
  public function getActiveThemeEntityId();

  /**
   * @return bool
   */
  public function getAdminThemeEntityId();

  /**
   * @return bool
   */
  public function getDefaultThemeEntityId();

  /**
   * @param $entity_id
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getThemeEntity($entity_id);

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAllThemeEntity();

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $theme_entity
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getLayoutEntity(ContentEntityInterface $theme_entity = NULL);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $theme_entity
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getRegionEntityForTheme(ContentEntityInterface $theme_entity = NULL);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $layout_entity
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getRegionEntityForLayout(ContentEntityInterface $layout_entity = NULL);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $theme_entity
   *
   * @return array|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getColorPalette(ContentEntityInterface $theme_entity = NULL);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $theme_entity
   *
   * @return array|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBreakpointEntityForTheme(ContentEntityInterface $theme_entity = NULL);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $theme_entity
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDomTagEntityForTheme(ContentEntityInterface $theme_entity = NULL);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $theme_entity
   *
   * @return array|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getComponentEntityForTheme(ContentEntityInterface $theme_entity = NULL);

  /**
   * @param $field_name
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $theme_entity
   *
   * @return array|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getThemeEntityDependency($field_name, ContentEntityInterface $theme_entity = NULL);

  /**
   * @param int $theme_entity_id
   *
   * @return string
   */
  public function getThemeNameFromEntityId($theme_entity_id);

  /**
   * @param $theme_name
   *
   * @return bool
   */
  public function parseThemeEntityIdFromName($theme_name);

  /**
   * @return array|mixed|null
   */
  public function getInstalledThemeName();

  /**
   * @param $name
   *
   * @return $this
   */
  public function setAdmin($name);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $theme
   */
  public function processThemeDelete(ContentEntityInterface $theme);

}
