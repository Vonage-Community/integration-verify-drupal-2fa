<?php

namespace Drupal\vonage_2fa\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EventSubscriber implements EventSubscriberInterface {
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
