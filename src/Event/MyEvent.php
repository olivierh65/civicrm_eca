<?php

namespace Drupal\civicrm_eca\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca_content\Event\ContentEntityBase;
use Drupal\eca\Plugin\ECA\Event\EventBase;


/**
 * Provides an event for civicrm_eca.
 *
 * @package Drupal\civicrm_eca\Event
 */
class MyEvent extends Event {

     /**
   * The ids.
   *
   * @var array
   */
  protected array $ids;

  /**
   * The entity type id.
   *
   * @var string
   */
  protected string $entityTypeId;

  /**
   * An instance holding event data accessible as Token.
   *
   * @var \Drupal\eca\Plugin\DataType\DataTransferObject|null
   */
  protected ?DataTransferObject $eventData = NULL;

  /**
   * Constructor.
   *
   * @param array $ids
   *   The ids.
   * @param string $entity_type_id
   *   The entity type id.
   */
  public function __construct(array $ids, string $entity_type_id) {
    $this->ids = $ids;
    $this->entityTypeId = $entity_type_id;
  }

  /**
   * Gets the ids.
   *
   * @return array
   *   The ids.
   */
  public function getIds(): array {
    return $this->ids;
  }

  /**
   * Gets the entity type id.
   *
   * @return string
   *   The entity type id.
   */
  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

}
