<?php

namespace Drupal\vonage_2fa\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EventSubscriber implements EventSubscriberInterface {
  public function checkFor2FA(RequestEvent $event) {
    $route = Url::fromRoute('vonage_2fa.pin_verify')->toString();

    if ($event->getRequest()->getRequestUri() === '/user/logout') {
      return;
    }

    if (\Drupal::currentUser()->isAuthenticated()) {
      $session = $event->getRequest()->getSession();

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

        // Send the verify pin and save it off?

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
