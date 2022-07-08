<?php

namespace Drupal\vonage_2fa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class VerifyPinForm extends FormBase
{
  const RESPONSE_VERIFICATION_PASSED = 'SUCCESS';
  const RESPONSE_VERIFICATION_SENT = '0';

    protected $client;
    protected $apiKey;
    protected $apiSecret;
    protected $phoneNumber;

    public function __construct()
    {
        $config = \Drupal::config('vonage_2fa.apisettings');
        $this->client = \Drupal::httpClient();
        $this->apiKey = $config->get('api_key');
        $this->apiSecret = $config->get('api_secret');
        $userDataService = \Drupal::service('user.data');
        $this->phoneNumber = $userDataService->get('vonage_2fa', \Drupal::currentUser()->id(), 'phone_number');
    }

    public function buildForm(array $form, FormStateInterface $form_state)
   {
      $phoneNumberTail = substr($this->phoneNumber, -4);
      $response = $this->client->post("https://api.nexmo.com/verify/json?&api_key=$this->apiKey&api_secret=$this->apiSecret&number=$this->phoneNumber&workflow_id=6&brand=Drupal2FA");
      $responseBody = json_decode($response->getBody()->getContents(), true);

       if ($responseBody['status'] !== self::RESPONSE_VERIFICATION_SENT) {
           $form_state->setError('There was a problem setting up the 2FA form');
           return;
       }

      $requestId = $responseBody['request_id'];

      return [
      'pin' => [
        '#type' => 'textfield',
        '#title' => $this->t("Enter the 4-6 digit pin that was sent to your number ending in $phoneNumberTail"),
      ],
      'request_id' => [
        '#type' => 'hidden',
        '#value' => $requestId
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
        return;
    }

    $requestId = $form_state->getValue('request_id');

    $response = $this->client->get("https://api.nexmo.com/verify/check/json?&api_key=$apiKey&api_secret=$apiSecret&request_id=$requestId&code=$pin");
    $responseBody = json_decode($response->getBody()->getContents(), true);

    if ($responseBody['status'] !== self::RESPONSE_VERIFICATION_PASSED) {
        $form_state->setErrorByName('pin', 'Your PIN was invalid');
        return;
    }
  }
}
