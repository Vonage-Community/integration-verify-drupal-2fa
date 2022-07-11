<?php

namespace Drupal\vonage_2fa\Form;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\UserDataInterface;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class VerifyPinForm extends FormBase
{
  const RESPONSE_VERIFICATION_PASSED = '0';

    protected Client $client;
    protected ImmutableConfig $config;
    protected UserDataInterface $userData;
    protected SessionInterface $session;

    public function __construct()
    {
      $this->client = \Drupal::httpClient();
      $this->userData = \Drupal::service('user.data');
      $this->config = \Drupal::config('vonage_2fa.apisettings');
      $this->session = \Drupal::request()->getSession();
    }

    public function buildForm(array $form, FormStateInterface $form_state)
   {
      $requestId = $this->session->get('vonage_2fa_request_id');
      $phoneNumberTail = substr(
        $this->userData->get('vonage_2fa', \Drupal::currentUser()->id(), 'phone_number'),
        -4
      );

      $form['pin'] = [
        '#type' => 'textfield',
        '#title' => $this->t("Enter the 4-6 digit pin that was sent to your number ending in ") . $phoneNumberTail,
      ];

      $form['request_id'] = [
        '#type' => 'hidden',
        '#value' => $requestId
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];

      return $form;
   }

  public function getFormId()
  {
    return 'vonage_2fa_verify_pin';
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $this->session->remove('vonage_2fa_request_id');
    $this->session->set('vonage_2fa_state', 'complete');

    if ($this->session->has('vonage_2fa_redirect_info')) {
      $redirect = new RedirectResponse($this->session->get('vonage_2fa_redirect_info'));
      $this->session->remove('vonage_2fa_redirect_info');
    } else {
      $redirect = new RedirectResponse('/');
    }

    $this->session->save();
    $redirect->send();
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $pin = $form_state->getValue('pin');

    if (strlen($pin) < 4 || strlen($pin) > 6) {
        $form_state->setErrorByName('pin', 'Incorrect PIN length');
        return;
    }

    $url = sprintf(
      "https://api.nexmo.com/verify/check/json?&api_key=%s&api_secret=%s&request_id=%s&code=%s",
      $this->config->get('api_key'),
      $this->config->get('api_secret').
      $form_state->getValue('request_id'),
      $pin
    );
    $response = $this->client->get($url);
    $responseBody = json_decode($response->getBody()->getContents(), true);

    if ($responseBody['status'] !== self::RESPONSE_VERIFICATION_PASSED) {
        $form_state->setErrorByName('pin', 'Your PIN was invalid');
        return;
    }
  }
}
