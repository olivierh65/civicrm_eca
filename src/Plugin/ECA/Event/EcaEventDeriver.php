<?php

namespace Drupal\civicrm_eca\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventDeriverBase;

/**
 * Deriver for civicrm_eca event plugins.
 */
class EcaEventDeriver extends EventDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function definitions(): array {
    return EcaEvent::definitions();
  }

}
