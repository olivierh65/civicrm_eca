<?php

namespace Drupal\civicrm_eca\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;

/**
 * Plugin implementation of the ECA condition "Do something".
 *
 * @EcaCondition(
 *   id = "civicrm_eca_do_something",
 *   label = @Translation("Do something"),
 *   description = @Translation(""),
 *   eca_version_introduced = "1.0.0",
 * )
 */
class DoSomethingCondition extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $node = $this->getValueFromContext('node');
    $result = TRUE;
    return $this->negationCheck($result);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'field1' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['field1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label of field 1'),
      '#default_value' => $this->configuration['field1'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['field1'] = $form_state->getValue('field1');
    parent::submitConfigurationForm($form, $form_state);
  }

}
