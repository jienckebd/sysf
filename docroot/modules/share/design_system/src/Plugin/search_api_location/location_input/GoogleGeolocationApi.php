<?php

namespace Drupal\design_system\Plugin\search_api_location\location_input;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_location\LocationInput\LocationInputPluginBase;

/**
 * Represents the Raw Location Input.
 *
 * @LocationInput(
 *   id = "google_geolocation_api",
 *   label = @Translation("Google Geolocation API"),
 *   description = @Translation("Determine user location based on Google Geolocation API call."),
 * )
 */
class Raw extends LocationInputPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getParsedInput(array $input) {

    $api_url = 'https://www.googleapis.com/geolocation/v1/geolocate?key=AIzaSyAE2MTYq2HT50RKGRWOPZwuVU2I5WuNb_Y';
    $payload = [];

    $payload['considerIp'] = TRUE;

    $payload_json = json_encode($payload);

    $http_client = \Drupal::httpClient();

    $response = $http_client->get($api_url, [
      'body' => $payload_json,
    ]);

    $response_body = json_decode($response->getBody(), TRUE);

    if (empty($input['value'])) {
      throw new \InvalidArgumentException('Input doesn\'t contain a location value.');
    }

    $input['value'] = trim($input['value']);
    return preg_match('/^[+-]?[0-9]+(?:\.[0-9]+)?,[+-]?[0-9]+(?:\.[0-9]+)?$/', $input['value']) ? $input['value'] : NULL;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement validateConfigurationForm() method.
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submitConfigurationForm() method.
  }

}
