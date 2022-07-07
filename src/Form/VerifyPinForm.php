<?php

namespace Drupal\vonage_2fa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class VerifyPinForm extends FormBase
{
  const RESPONSE_VERIFICATION_PASSED = 'SUCCESS';

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    return [
      'pin' => [
        '#type' => 'textfield',
        '#title' => $this->t('Enter the 4-6 digit pin that was sent to you'),
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ]
    ];
  }

  public function getFormId()
  {
    return 'vonage_2fa_verify_pin';
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Should have been a successful validate, so we can just flag they authed
    $session = \Drupal::request()->getSession();
    $session->set('2fa_verified', true);
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $pin = $form_state->getValue('pin');

    if (strlen($pin) < 4 || strlen($pin) > 6) {
        $form_state->setErrorByName('pin', 'Incorrect PIN length');
    }
    $config = \Drupal::config('vonage_2fa.apisettings');
    $apiKey = $config->get('api_key');
    $apiSecret = $config->get('api_secret');
    $session = \Drupal::request()->getSession();
    $requestId = $session->get('request_id');

    $client = \Drupal::httpClient();
    $response = $client->get("https://api.nexmo.com/verify/check/json?&api_key=$apiKey&api_secret=$apiSecret&request_id=$requestId&code=$pin");
    $responseBody = json_decode($response->getBody(), true);

    if ($responseBody['status'] !== self::RESPONSE_VERIFICATION_PASSED) {
        $form_state->setErrorByName('pin', 'Your PIN was invalid');
    }

    $session->remove('request_id');
    $session->set('saved_request_id');
  }
}
