<?php

namespace Drupal\vonage_2fa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class VonageUserSettingsForm extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'user_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $user = \Drupal::currentUser();
    $userDataService = \Drupal::service('user.data');

    $form['phone_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone Number'),
      '#description' => $this->t('Your phone number needs to have the full international dialling code i.e. +44774xxxxxx'),
      '#default_value' => $userDataService->get('vonage_2fa', $user->id(), 'phone_number')
    ];

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Check to enable 2FA on your account'),
      '#default_value' => $userDataService->get('vonage_2fa', $user->id(), 'enabled')
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save')
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $user = \Drupal::currentUser();
    $userDataService = \Drupal::service('user.data');
    $userDataService->set('vonage_2fa', $user->id(), 'phone_number', $form_state->getValue('phone_number'));
    $userDataService->set('vonage_2fa', $user->id(), 'enabled', $form_state->getValue('enabled'));
  }
}
