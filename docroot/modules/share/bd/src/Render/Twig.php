<?php

namespace Drupal\bd\Render;

/**
 * Class Twig.
 */
class Twig {

  /**
   * @var \Twig_Environment
   */
  protected $engine;

  /**
   * @var array
   */
  protected $skeletonDirs;

  /**
   * @var StringConverter
   */
  protected $stringConverter;

  /**
   * Twig constructor.
   *
   * @param \Drupal\bd\Render\StringConverter $stringConverter
   */
  public function __construct(
    StringConverter $stringConverter = NULL
  ) {
    $this->stringConverter = $stringConverter ?: new StringConverter();
  }

  /**
   * @param array $skeletonDirs
   */
  public function setSkeletonDirs(array $skeletonDirs) {
    foreach ($skeletonDirs as $skeletonDir) {
      $this->addSkeletonDir($skeletonDir);
    }
  }

  /**
   * @param $skeletonDir
   */
  public function addSkeletonDir($skeletonDir) {
    if (is_dir($skeletonDir)) {
      $this->skeletonDirs[] = $skeletonDir;
    }
  }

  /**
   * @return array
   */
  public function getSkeletonDirs() {

    if (!$this->skeletonDirs) {
      /** @var \Drupal\bd\File\FileSystemInterface $file_system */
      $file_system = \Drupal::service('file_system');

      if (!$dir = $file_system->getModuleListWithAsset("config/template")) {
        return [];
      }

      $this->skeletonDirs = $dir;
    }

    return $this->skeletonDirs;
  }

  /**
   * @param string $template
   * @param array $parameters
   *
   * @return string
   */
  public function render($template, $parameters = []) {
    if (!$this->engine) {
      $this->engine = new \Twig_Environment(
        new \Twig_Loader_Filesystem($this->getSkeletonDirs()), [
          'debug' => TRUE,
          'cache' => FALSE,
          'strict_variables' => TRUE,
          'autoescape' => FALSE,
        ]
      );

      $this->engine->addFunction($this->getServicesAsParameters());
      $this->engine->addFunction($this->getServicesAsParametersKeys());
      $this->engine->addFunction($this->getArgumentsFromRoute());
      $this->engine->addFunction($this->getServicesClassInitialization());
      $this->engine->addFunction($this->getServicesClassInjection());
      $this->engine->addFunction($this->getTagsAsArray());
      $this->engine->addFunction($this->getTranslationAsYamlComment());
      $this->engine->addFilter($this->createMachineName());
    }

    return $this->engine->render($template, $parameters);
  }

  /**
   * @return \Twig_SimpleFunction
   */
  public function getServicesAsParameters() {
    $servicesAsParameters = new \Twig_SimpleFunction(
      'servicesAsParameters', function ($services) {
        $returnValues = [];
        foreach ($services as $service) {
          $returnValues[] = sprintf('%s $%s', $service['short'], $service['machine_name']);
        }

        return $returnValues;
      }
    );

    return $servicesAsParameters;
  }

  /**
   * @return \Twig_SimpleFunction
   */
  public function getServicesAsParametersKeys() {
    $servicesAsParametersKeys = new \Twig_SimpleFunction(
      'servicesAsParametersKeys', function ($services) {
        $returnValues = [];
        foreach ($services as $service) {
          $returnValues[] = sprintf('\'@%s\'', $service['name']);
        }

        return $returnValues;
      }
    );

    return $servicesAsParametersKeys;
  }

  /**
   * @return \Twig_SimpleFunction
   */
  public function getArgumentsFromRoute() {
    $argumentsFromRoute = new \Twig_SimpleFunction(
      'argumentsFromRoute', function ($route) {
        $returnValues = '';
        preg_match_all('/{(.*?)}/', $route, $returnValues);

        $returnValues = array_map(
        function ($value) {
          return sprintf('$%s', $value);
        }, $returnValues[1]
        );

        return $returnValues;
      }
    );

    return $argumentsFromRoute;
  }

  /**
   * @return \Twig_SimpleFunction
   */
  public function getServicesClassInitialization() {
    $returnValue = new \Twig_SimpleFunction(
      'serviceClassInitialization', function ($services) {
        $returnValues = [];
        foreach ($services as $service) {
          $returnValues[] = sprintf('    $this->%s = $%s;', $service['camel_case_name'], $service['machine_name']);
        }

        return implode(PHP_EOL, $returnValues);
      }
    );

    return $returnValue;
  }

  /**
   * @return \Twig_SimpleFunction
   */
  public function getServicesClassInjection() {
    $returnValue = new \Twig_SimpleFunction(
      'serviceClassInjection', function ($services) {
        $returnValues = [];
        foreach ($services as $service) {
          $returnValues[] = sprintf('      $container->get(\'%s\')', $service['name']);
        }

        return implode(',' . PHP_EOL, $returnValues);
      }
    );

    return $returnValue;
  }

  /**
   * @return \Twig_SimpleFunction
   */
  public function getTagsAsArray() {
    $returnValue = new \Twig_SimpleFunction(
      'tagsAsArray', function ($tags) {
        $returnValues = [];
        foreach ($tags as $key => $value) {
          $returnValues[] = sprintf('%s: %s', $key, $value);
        }

        return $returnValues;
      }
    );

    return $returnValue;
  }

  /**
   * @return \Twig_SimpleFunction
   */
  public function getTranslationAsYamlComment() {
    $returnValue = new \Twig_SimpleFunction(
      'yaml_comment', function (\Twig_Environment $environment, $context, $key) {
        $message = $this->translator->trans($key);
        $messages = explode("\n", $message);
        $returnValues = [];
        foreach ($messages as $message) {
          $returnValues[] = '# ' . $message;
        }

        $message = implode("\n", $returnValues);
        $template = $environment->createTemplate($message);

        return $template->render($context);
      }, [
        'needs_environment' => TRUE,
        'needs_context' => TRUE,
      ]
    );

    return $returnValue;
  }

  /**
   * @return \Twig_SimpleFilter
   */
  public function createMachineName() {
    return new \Twig_SimpleFilter(
      'machine_name', function ($var) {
        return $this->stringConverter->createMachineName($var);
      }
    );
  }

}
