<?php

namespace Sysf\Blt\Plugin\Traits;

use Acquia\Blt\Robo\Common\Io;

trait IoTrait {

  use Io;

  /**
   * Writes an error to the CLI.
   *
   * @param string $text
   *   The text to write.
   * @param int $length
   *   The length at which text should be wrapped.
   * @param string $color
   *   The color of the text.
   */
  protected function success($text, $length = 40, $color = 'green') {
    $format = "<fg=white;bg=$color;options=bold>%s</fg=white;bg=$color;options=bold>";
    $this->formattedOutput($text, $length, $format);
  }

  /**
   * Writes a notice to the CLI, typically used prior to performing a task.
   *
   * @param string $text
   *   The text to write.
   * @param int $length
   *   The length at which text should be wrapped.
   * @param string $color
   *   The color of the text.
   */
  protected function notice($text, $length = 40, $color = 'green') {
    $format = "<fg=white;bg=$color;options=bold>%s</fg=white;bg=$color;options=bold>";
    $this->formattedOutput($text, $length, $format);
  }

  /**
   * Writes a warning to the CLI.
   *
   * @param string $text
   *   The text to write.
   * @param int $length
   *   The length at which text should be wrapped.
   * @param string $color
   *   The color of the text.
   */
  protected function warning($text, $length = 40, $color = 'yellow') {
    $format = "<fg=white;bg=$color;options=bold>%s</fg=white;bg=$color;options=bold>";
    $this->formattedOutput($text, $length, $format);
  }

  /**
   * Writes an error to the CLI.
   *
   * @param string $text
   *   The text to write.
   * @param int $length
   *   The length at which text should be wrapped.
   * @param string $color
   *   The color of the text.
   */
  protected function error($text, $length = 40, $color = 'red') {
    $format = "<fg=white;bg=$color;options=bold>%s</fg=white;bg=$color;options=bold>";
    $this->formattedOutput($text, $length, $format);
  }

}
