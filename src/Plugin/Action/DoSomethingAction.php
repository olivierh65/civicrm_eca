<?php

namespace Drupal\civicrm_eca\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Attributes\Token;

/**
 * Describes the civicrm_eca do_something action.
 *
 * @Action(
 *   id = "civicrm_eca_do_something",
 *   label = @Translation("Do something"),
 *   description = @Translation(""),
 *   eca_version_introduced = "1.0.0" * )
 */
class DoSomethingAction extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE): bool|AccessResultInterface {
    $access_result = AccessResult::allowed();
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(mixed $entity = NULL): void {
    // @todo implement the required action.
    $entity=$this->tokenService->getTokenData('entity');
    $contact_id=$entity->get('contact_id')->getString();

    $this->tokenService->addTokenData('civi_contact_id', $contact_id);

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'civi_contact_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['civi_contact_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Civicrm contact ID'),
      '#default_value' => $this->configuration['civi_contact_id'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['civi_contact_id'] = $form_state->getValue('civi_contact_id');
    parent::submitConfigurationForm($form, $form_state);
  }

}
