<?php

namespace Drupal\custom_events_and_subscribers\Event;

use Drupal\user\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that is fired when a user logs in.
 */
class UserLoginEvent extends Event {

    const EVENT_NAME = 'custom_events_user_login';

    /**
     * The user account.
     *
     * @var \Drupal\user\UserInterface
     */
    public $account;

    public function __construct(UserInterface $account) {
        $this->account = $account;
    }
}