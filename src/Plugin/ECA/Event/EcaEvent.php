<?php

namespace Drupal\civicrm_eca\Plugin\ECA\Event;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Attributes\Token;
use Drupal\eca\Event\Tag;
use Drupal\eca\Plugin\CleanupInterface;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\civicrm_eca\EcaEvents;
use Drupal\civicrm_eca\Event\MyEvent;
use Symfony\Contracts\EventDispatcher\Event;
use Drupal\eca\Service\ContentEntityTypes;


/**
 * Plugin implementation of the ECA Events for civicrm_eca.
 *
 * @EcaEvent(
 *   id = "civicrm_eca",
 *   deriver = "Drupal\civicrm_eca\Plugin\ECA\Event\EcaEventDeriver",
 *   description = @Translation(""),
 *   eca_version_introduced = "1.0.0"
 * )
 */
class EcaEvent extends EventBase implements CleanupInterface {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $definitions = [];
    $definitions['event1'] = [
      'label' => 'ECA for Civicrm',
      'description' => '',
      'event_name' => EcaEvents::IDENTIFIER1,
      'event_class' => MyEvent::class,
      'tags' => Tag::READ | Tag::BEFORE,
    ];
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $values = [
      'type' => ContentEntityTypes::ALL,
    ];
    return $values + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    if ($this->eventClass() === MyEvent::class) {
      // @todo Extend the form for this event.
    }
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    if ($this->eventClass() === MyEvent::class) {
      // @@todo Provide the submit functionality for this event.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generateWildcard(string $eca_config_id,
     /* EcaEvent */ $ecaEvent): string {
    /** @var \Drupal\eca\Plugin\ECA\Event\EventBase $plugin */
    $plugin = $ecaEvent->getPlugin();
    switch ($plugin->getDerivativeId()) {

      case 'event1':
        return '*';

      default:
        return '*';

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function appliesForWildcard(Event $event, string $event_name, string $wildcard): bool {
    if ($event instanceof MyEvent) {
      return TRUE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupAfterSuccessors(): void {
    switch ($this->getDerivativeId()) {

      case 'event1':
        // @todo Do something if necessary.

      
    }
  }

  /**
   * {@inheritdoc}
   */
  #[Token(
    name: 'event',
    description: 'The event.',
    classes: [
      MyEvent::class,
    ],
    properties: [
      new Token(name: 'my_event_token', description: 'Some description.'),
    ],
  )]
  protected function buildEventData(): array {
    $event = $this->event;
    $data = [];

    if ($event instanceof MyEvent) {
      $data += [
        'my_event_token' => 'Some data',
      ];
    }

    $data += parent::buildEventData();
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  #[Token(
    name: 'another_token',
    description: 'Some other description.',
    classes: [
      MyEvent::class,
    ],
  )]
  public function getData(string $key): mixed {
    $event = $this->event;
    if ($key === 'another_token' && $event instanceof MyEvent) {
      return 'some other data';
    }
    return parent::getData($key);
  }

}
