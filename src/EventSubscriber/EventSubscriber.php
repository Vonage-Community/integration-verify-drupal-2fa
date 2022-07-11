<?php

namespace Drupal\vonage_2fa\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EventSubscriber implements EventSubscriberInterface {
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

    public function checkFor2FA(RequestEvent $event) {
    $config = \Drupal::config('vonage_2fa.apisettings');
    if (!$config->get('enabled')) {
      return;
    }

    $route = Url::fromRoute('vonage_2fa.pin_verify')->toString();

    if ($event->getRequest()->getRequestUri() === '/' . \Drupal::languageManager()->getCurrentLanguage()->getId() . '/user/logout') {
      return;
    }

    if (\Drupal::currentUser()->isAuthenticated()) {
      $session = $event->getRequest()->getSession();

      $userDataService = \Drupal::service('user.data');
      $enabled = $userDataService->get('vonage_2fa', \Drupal::currentUser()->id(), 'enabled');

      if (!$enabled) {
          return;
      }

      if ($session->has('2fa_verified')) {
        if ($event->getRequest()->getRequestUri() === $route) {
          if ($session->has('2fa_redirect_info')) {
            $info = $session->get('2fa_redirect_info');
            $session->remove('2fa_redirect_info');
            $session->save();
            $event->setResponse(new RedirectResponse($info['uri']));
            return;
          }
        }
        return;
      } else {
          try {
              $phoneNumber = $this->userDataService->get('vonage_2fa', \Drupal::currentUser()->id(), 'phone_number');
              $response = $this->client->get("https://api.nexmo.com/verify/json?&api_key=$this->apiKey&api_secret=$this->apiSecret&number=$phoneNumber&workflow_id=6&brand=Drupal2FA");
          } catch (\GuzzleHttp\Exception\ClientException $exception) {
              \Drupal::messenger()->adderror('There is an error with your Two-Factor Authentication provider, please check your account.');
              $url = Url::fromRoute('vonage_2fa.error');
              return new RedirectResponse($url->toString());
          }

          $responseBody = json_decode($response->getBody()->getContents(), true);

          if ($responseBody['status'] !== self::RESPONSE_VERIFICATION_SENT) {
              \Drupal::messenger()->addError('There is a problem with your Two Factor Authentication provider, please contact your administrator', TRUE);
              $url = Url::fromRoute('vonage_2fa.error');
              return new RedirectResponse($url->toString());
          }

          $session = $event->getRequest()->getSession();
          $session->set('request_id', $responseBody['request_id']);
          $session->save();
      }

      if ($event->getRequest()->getRequestUri() !== $route) {
        $session->set('2fa_redirect_info', [
          'uri' => $event->getRequest()->getRequestUri(),
          'params' => $event->getRequest()->getQueryString()
        ]);
        $session->save();

        $event->setResponse(new RedirectResponse($route));
        return;
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
