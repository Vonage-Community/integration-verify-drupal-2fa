<?php

namespace Drupal\vonage_2fa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class VerifyPinForm extends FormBase
{
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
      $form_state->setErrorByName('pin', 'Incorrect pin length');
    }

    // Validate the pin here
  }
}
