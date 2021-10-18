<?php

namespace Drupal\bd\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'User Permission' condition.
 *
 * @Condition(
 *   id = "user_permission",
 *   label = @Translation("User Permission"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   }
 * )
 */
class UserPermission extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Permission condition plugin.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin_id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->permissionHandler = $permission_handler;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('user.permissions'),
      $container->get('module_handler'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'permission' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['permission'] = [
      '#type' => 'select',
      '#options' => $this->permissionOptions(),
      '#empty_value' => '',
      '#title' => $this->t('Permission'),
      '#default_value' => $this->configuration['permission'],
      '#description' => $this->t('Check that current user has the selected permission.'),
      '#normalize' => TRUE,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['permission'] = $form_state->getValue('permission');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $permission = $this->configuration['permission'];
    $permissionTitle = $this->permissionTitle($permission);
    if ($this->isNegated()) {
      return $this->t('The user does not have the permission "@permission"', ['@permission' => $permissionTitle]);
    }
    else {
      return $this->t('The user has the permission "@permission"', ['@permission' => $permissionTitle]);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function evaluate() {
    if (empty($this->configuration['permission'])) {
      return $this->isNegated() ? FALSE : TRUE;
    }
    $user = $this->getContextValue('user');
    if ($user instanceof UserInterface) {
      return $user->hasPermission($this->configuration['permission']);
    }
  }

  /**
   * Make permission options (grouped by provider name).
   *
   * @return array
   */
  protected function permissionOptions() {
    $permissionOptions = [];
    $permissions = $this->permissionHandler->getPermissions();
    foreach ($permissions as $perm => $perm_item) {
      $provider = $perm_item['provider'];
      $providerName = $this->moduleHandler->getName($provider);
      $permissionOptions[$providerName][$perm] = strip_tags($perm_item['title']);
    }
    return $permissionOptions;
  }

  /**
   * Make permission titles.
   *
   * @return \Drupal\Component\Render\MarkupInterface[]
   */
  protected function permissionTitles() {
    $permissionOptions = [];
    $permissions = $this->permissionHandler->getPermissions();
    foreach ($permissions as $perm => $perm_item) {
      $provider = $perm_item['provider'];
      $providerName = $this->moduleHandler->getName($provider);
      $tArgs = ['@permission_title' => $perm_item['title'], '@module_name' => $providerName];
      $permissionOptions[$perm] = $this->t('@permission_title (@module_name)', $tArgs);
    }
    return $permissionOptions;
  }

  /**
   * @param $permission
   *
   * @return \Drupal\Component\Render\MarkupInterface
   */
  protected function permissionTitle($permission) {
    return $this->permissionTitles()[$permission] ?? $this->t('- Invalid permission -');
  }

}
