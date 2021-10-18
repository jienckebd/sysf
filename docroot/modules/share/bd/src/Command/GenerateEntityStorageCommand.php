<?php

namespace Drupal\bd\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Command\Shared\ModuleTrait;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class GenerateEntityStorageCommand.
 *
 * @DrupalCommand (
 *     extension="bd",
 *     extensionType="module"
 * )
 */
class GenerateEntityStorageCommand extends ContainerAwareCommand {
  use ModuleTrait;
  private $entityType;
  private $commandName;

  /**
   * @param $entityType
   */
  protected function setEntityType($entityType) {
    $this->entityType = $entityType;
  }

  /**
   * @param $commandName
   */
  protected function setCommandName($commandName) {
    $this->commandName = $commandName;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $commandKey = str_replace(':', '.', $this->commandName);

    $this
      ->setName($this->commandName)
      ->setDescription(
        sprintf(
          $this->trans('commands.' . $commandKey . '.description'),
          $this->entityType
        )
      )
      ->setHelp(
        sprintf(
          $this->trans('commands.' . $commandKey . '.help'),
          $this->commandName,
          $this->entityType
        )
      )
      ->addOption('module', NULL, InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
      ->addOption(
        'entity-class',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.' . $commandKey . '.options.entity-class')
      )
      ->addOption(
        'entity-name',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.' . $commandKey . '.options.entity-name')
      )
      ->addOption(
        'base-path',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.' . $commandKey . '.options.base-path')
      )
      ->addOption(
        'label',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.' . $commandKey . '.options.label')
      );
  }

  /**
   *
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $module = $input->getOption('module');
    $entity_class = $input->getOption('entity-class');
    $entity_name = $input->getOption('entity-name');
    $label = $input->getOption('label');
    $has_bundles = $input->getOption('has-bundles');
    $base_path = $input->getOption('base-path');
    $learning = $input->hasOption('learning') ? $input->getOption('learning') : FALSE;
    $bundle_entity_type = $has_bundles ? $entity_name . '_type' : NULL;
    $is_translatable = $input->hasOption('is-translatable') ? $input->getOption('is-translatable') : TRUE;
    $revisionable = $input->hasOption('revisionable') ? $input->getOption('revisionable') : FALSE;

    $generator = $this->generator;

    $generator->setIo($this->getIo());
    // @todo
    // $generator->setLearning($learning);
    $generator->generate([
      'module' => $module,
      'entity_name' => $entity_name,
      'entity_class' => $entity_class,
      'label' => $label,
      'bundle_entity_type' => $bundle_entity_type,
      'base_path' => $base_path,
      'is_translatable' => $is_translatable,
      'revisionable' => $revisionable,
    ]);

    if ($has_bundles) {
      $this->chainQueue->addCommand(
        'generate:entity:config', [
          '--module' => $module,
          '--entity-class' => $entity_class . 'Type',
          '--entity-name' => $entity_name . '_type',
          '--label' => $label . ' type',
          '--bundle-of' => $entity_name,
        ]
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $commandKey = str_replace(':', '.', $this->commandName);
    $utils = $this->stringConverter;

    // --module option
    $this->getModuleOption();

    // --entity-class option
    $entityClass = $input->getOption('entity-class');
    if (!$entityClass) {
      $entityClass = $this->getIo()->ask(
        $this->trans('commands.' . $commandKey . '.questions.entity-class'),
        'DefaultEntity',
        function ($entityClass) {
          return $this->validator->validateSpaces($entityClass);
        }
      );

      $input->setOption('entity-class', $entityClass);
    }

    // --entity-name option
    $entityName = $input->getOption('entity-name');
    if (!$entityName) {
      $entityName = $this->getIo()->ask(
        $this->trans('commands.' . $commandKey . '.questions.entity-name'),
        $utils->camelCaseToMachineName($entityClass),
        function ($entityName) {
          return $this->validator->validateMachineName($entityName);
        }
      );
      $input->setOption('entity-name', $entityName);
    }

    // --label option
    $label = $input->getOption('label');
    if (!$label) {
      $label = $this->getIo()->ask(
        $this->trans('commands.' . $commandKey . '.questions.label'),
        $utils->camelCaseToHuman($entityClass)
      );
      $input->setOption('label', $label);
    }

    // --base-path option
    $base_path = $input->getOption('base-path');
    if (!$base_path) {
      $base_path = $this->getDefaultBasePath();
    }
    $base_path = $this->getIo()->ask(
      $this->trans('commands.' . $commandKey . '.questions.base-path'),
      $base_path
    );
    if (substr($base_path, 0, 1) !== '/') {
      // Base path must start with a leading '/'.
      $base_path = '/' . $base_path;
    }
    $input->setOption('base-path', $base_path);
  }

  /**
   * Gets default base path.
   *
   * @return string
   *   Default base path.
   */
  protected function getDefaultBasePath() {
    return '/admin/structure';
  }

}
