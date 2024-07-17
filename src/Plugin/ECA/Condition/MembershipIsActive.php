<?php

namespace Drupal\civicrm_eca\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;
use Drupal\eca_content\Event\ContentEntityPreSave;
use Drupal\eca\Attributes\Token;

use Drupal\user\Entity\User;
use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\civicrm\Civicrm;





/**
 * Plugin implementation of the ECA condition "Drupal User Exist".
 *
 * @EcaCondition(
 *   id = "civicrm_eca_membership_is_active",
 *   label = @Translation("Civicrm Membership is active"),
 *   description = @Translation("IS membership active?"),
 *   eca_version_introduced = "1.0.0",
 * )
 */
class MembershipIsActive extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    
    if (!empty($this->configuration['civi_membership_id'])) {
      $civi_membership_id = $this->configuration['civi_membership_id'];
    } else {
      $entity = $this->tokenService->getTokenData('entity');
      if ((!$entity instanceof \Drupal\civicrm_entity\Entity\CivicrmEntity) && ($entity->entityTypeId != "civicrm_membership")) {
        throw new \InvalidArgumentException(sprintf("Given entity %s is not supported.", $entity->entityTypeId ?? "is not a civicrm_membership entity"));
      }
      //retreive the membership_id
      $civi_membership_id = $entity->get('id')->getString();
    }
    

    try {
      // Perfom civicrm bootstrap
      \Drupal::service('civicrm')->initialize();
      
      $memberships = \Civi\Api4\Membership::get(FALSE)
        ->addSelect('status_id', 'status_id:label', 'status_id:name', 'status_id.is_active')
        ->addWhere('id', '=', $civi_membership_id)
        ->execute();

      if ($memberships->count() == 0) {
        return $this->negationCheck(false);
      }

      if ($memberships->first()['status_id.is_active'] == true) {
        return $this->negationCheck(true);
      }
      else {
        return $this->negationCheck(false);
      }
    } catch (\Exception $e) {
      return $this->negationCheck(FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'civi_membership_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Tokens <code>civicrm_membership:id</code>, <code>civicrm_membership:status_label</code>
      <code>civicrm_membership:status_id</code> and <code>civicrm_membership:status_id</code> are created'),
      '#weight' => 10,
    ];
    $form['civi_membership_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Civicrm Membership ID'),
      '#default_value' => $this->configuration['civi_membership_id'],
      '#description' => $this->t('Compare Membership status.<br>
      If Civicrm <code>Membership ID</code> is empty, the <code>id</code> is searched in the entity (type <code>civicrm_membership</code>) returned by the event.'),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['civi_membership_status'] = $form_state->getValue('civi_membership_status');
    $this->configuration['civi_membership_id'] = $form_state->getValue('civi_membership_id');
    parent::submitConfigurationForm($form, $form_state);
  }
}