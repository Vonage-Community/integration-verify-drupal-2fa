<?php

namespace Drupal\vonage_2fa\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EventSubscriber implements EventSubscriberInterface
{
  protected $client;
  protected $apiKey;
  protected $apiSecret;
  protected $phoneNumber;

  const RESPONSE_VERIFICATION_SENT = '0';

  public function __construct()
  {
    $config = \Drupal::config('vonage_2fa.apisettings');
    $this->client = \Drupal::httpClient();
    $this->apiKey = $config->get('api_key');
    $this->apiSecret = $config->get('api_secret');
    $this->userDataService = \Drupal::service('user.data');
  }

  public function checkFor2FA(RequestEvent $event)
  {
    $config = \Drupal::config('vonage_2fa.apisettings');
    if (!$config->get('enabled')) {
      return;
    }

    if (\Drupal::currentUser()->isAuthenticated()) {
      // User is logging out, never stop this action
      if ($event->getRequest()->getRequestUri() === Url::fromRoute('user.logout')->toString()) {
        return;
      }

      // User is logged in and we verified the 2FA, let the request go through
      if (\Drupal::request()->getSession()->has('vonage_2fa_state') && \Drupal::request()->getSession()->get('vonage_2fa_state') === 'complete') {
        return;
      }

      // User is logged in, we are going to the verify form, and we are on the verify step, let the request go through
      if (
        \Drupal::request()->getSession()->has('vonage_2fa_state')
        && \Drupal::request()->getSession()->get('vonage_2fa_state') === 'verify'
        && $event->getRequest()->getRequestUri() === Url::fromRoute('vonage_2fa.pin_verify')->toString()
      ) {
        return;
      }

      // User is logged in but we have no state - start the process and redirect to the form
      if (!\Drupal::request()->getSession()->has('vonage_2fa_state')) {
        \Drupal::request()->getSession()->set('vonage_2fa_state', 'verify');

        // Send the the request and redirect to the form
        $phoneNumber = $this->userDataService->get('vonage_2fa', \Drupal::currentUser()->id(), 'phone_number');
        $response = $this->client->get("https://api.nexmo.com/verify/json?&api_key=$this->apiKey&api_secret=$this->apiSecret&number=$phoneNumber&workflow_id=6&brand=Drupal2FA");
        $responseBody = json_decode($response->getBody()->getContents(), true);
        \Drupal::request()->getSession()->set('vonage_2fa_request_id', $responseBody['request_id']);
        \Drupal::request()->getSession()->set('vonage_2fa_redirect_info', $event->getRequest()->getRequestUri());

        \Drupal::request()->getSession()->save();
        $event->setResponse((new RedirectResponse(Url::fromRoute('vonage_2fa.pin_verify')->toString())));
      }
    }
  }

  public static function getSubscribedEvents()
  {
    return [
      KernelEvents::REQUEST => [
        ['checkFor2FA']
      ]
    ];
  }
}
