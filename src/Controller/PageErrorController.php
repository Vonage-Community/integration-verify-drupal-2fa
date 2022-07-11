<?php
namespace Drupal\vonage_2fa\Controller;

use Drupal\Core\Controller\ControllerBase;

class PageErrorController extends ControllerBase {

    /**
     * Returns a render-able array for a test page.
     */
    public function content() {
        $build = [
            '#markup' => $this->t('Hello World!'),
        ];
        return $build;
    }
}