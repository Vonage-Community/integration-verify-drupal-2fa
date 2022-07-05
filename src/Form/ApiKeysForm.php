<?php

namespace Drupal\vonage_2fa\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ApiKeysForm extends ConfigFormBase {
	/**
	 * {@inheritdoc}
	 */
	protected function getEditableConfigNames() {
		return [
			'vonage_2fa.apisettings',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'admin_form';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$config = $this->config('vonage_2fa.apisettings');

		$form['api_key'] = [
			'#type' => 'textfield',
			'#title' => $this->t('API Key'),
			'#description' => $this->t('This is your Vonage API Key'),
			'#default_value' => $config->get('api_key'),
		];

		$form['api_secret'] = [
			'#type' => 'textfield',
			'#title' => $this->t('API Secret'),
			'#description' => $this->t('This is your Vonage API Secret'),
			'#default_value' => $config->get('api_secret'),
		];

        $form['enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enabled'),
            '#description' => $this->t('Check to enable 2FA'),
            '#default_value' => $config->get('enabled')
        ];

		return parent::buildForm($form, $form_state);
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		parent::submitForm($form, $form_state);

		$this->config('vonage_2fa.apisettings')
		     ->set('api_key', $form_state->getValue('api_key'))
			 ->set('api_secret', $form_state->getValue('api_secret'))
             ->set('enabled', $form_state->getValue('enabled'))
		     ->save();
	}
}