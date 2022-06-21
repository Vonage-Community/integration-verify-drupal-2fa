<?php

/**
 * @file
 * Contains Drupal\welcome\Form\MessagesForm.
 */
namespace Drupal\vonage_2fa\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ApiKeysForm extends ConfigFormBase {
	/**
	 * {@inheritdoc}
	 */
	protected function getEditableConfigNames() {
		return [
			'vonage_2fa.adminsettings',
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
		$config = $this->config('vonage_2fa.adminsettings');

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

		return parent::buildForm($form, $form_state);
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		parent::submitForm($form, $form_state);

		$this->config('vonage_2fa.adminsettings')
		     ->set('api_key', $form_state->getValue('api_key'))
			 ->set('api_secret', $form_state->getValue('api_secret'))
		     ->save();
	}
}