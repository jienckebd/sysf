<?php

namespace Sysf\Blt\Traits;

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
  protected function success($text, $length = 80, $color = 'green') {
    $format = "<fg=black;bg=$color;options=bold> Success </fg=black;bg=$color;options=bold> %s";
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
  protected function notice($text, $length = 80, $color = 'blue') {
    $format = "<fg=black;bg=$color;options=bold> Notice  </fg=black;bg=$color;options=bold> %s";
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
  protected function warning($text, $length = 80, $color = 'yellow') {
    $format = "<fg=black;bg=$color;options=bold> Warning </fg=black;bg=$color;options=bold> %s";
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
  protected function error($text, $length = 80, $color = 'red') {
    $format = "<fg=black;bg=$color;options=bold> Error   </fg=black;bg=$color;options=bold> %s";
    $this->formattedOutput($text, $length, $format);
  }

    /**
     * @param string $text
     * @param int $length
     * @param string $format
     */
    protected function formattedOutput($text, $length, $format) {
      $lines = explode("\n", trim($text, "\n"));
      foreach ($lines as $line) {
        $this->writeln(sprintf($format, " $line "));
      }
    }


}
