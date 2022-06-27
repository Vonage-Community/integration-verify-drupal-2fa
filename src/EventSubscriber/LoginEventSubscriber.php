<?php

namespace Drupal\vonage_2fa\EventSubscriber;

use Drupal\vonage_2fa\Event\UserLoginEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Messenger\MessengerInterface;

class LoginEventSubscriber implements EventSubscriberInterface {

    public function __construct() {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserLoginEvent::EVENT_NAME => 'onUserLogin',
        ];
    }

    public function onUserLogin(UserLoginEvent $event): void
    {
        // is 2fa enabled for this user?
        // get the request id hash
        // is it valid
        // no hash? we are submitting a pin
        // is it valid
        // no pin? we are trying to log in, render the pin page

        $username = $event->account->getAccountName();

        $this->messenger
            ->addStatus('<strong>Hey there</strong>: %name.',
                [
                    '%name' => $username,
                ]
            );
    }
}