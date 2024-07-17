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
 *   id = "civicrm_eca_membership_compare",
 *   label = @Translation("Civicrm Membership status compare"),
 *   description = @Translation("Compare a membership status"),
 *   eca_version_introduced = "2.0.0",
 * )
 */
class MembershipCompareCondition extends ConditionBase {

  /**
   * Membership status
   *
   * @var array
   */
  private $options = null;

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    
    if (!empty($this->configuration['civi_membership_id'])) {
      $civi_membership_id = $this->configuration['civi_membership_id'];
    } else {
      $entity = $this->tokenService->getTokenData('entity');
      if ((!$entity instanceof CivicrmEntity) && ($entity->entityTypeId != "civicrm_membership")) {
        throw new \InvalidArgumentException(sprintf("Given entity %s is not supported.", $entity->entityTypeId ?? "is not a Civicrm_entity ans "));
      }
      //retreive the membership_id
      $civi_membership_id = $entity->get('id')->getString();
    }
    

    try {
      $memberships = \Civi\Api4\Membership::get(FALSE)
        ->addSelect('status_id', 'status_id:label', 'status_id:name', 'status_id.is_active')
        ->addWhere('id', '=', $civi_membership_id)
        ->execute();

      if ($memberships->count() == 0) {
        return $this->negationCheck(false);
      }

      if ((isset($this->configuration['civi_membership_status'])) && 
      ($this->configuration['civi_membership_status']) == $memberships->first()['status_id']) {
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
    if ($this->options == null) {
      // Perfom civicrm bootstrap
      \Drupal::service('civicrm')->initialize();

      $membershipStatuses = \Civi\Api4\MembershipStatus::get(FALSE)
        ->addSelect('id', 'label')
        ->addWhere('is_active', '=', TRUE)
        ->execute();


      // #multiple is not translated in Dropdown !!
      $this->options = [];
      foreach ($membershipStatuses as $membershipStatus) {
        $this->options[$membershipStatus['id']] = $membershipStatus['label'];
      }
    }
    return [
      'civi_membership_status' => array_keys($this->options)[0],
      'civi_membership_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['civi_membership_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Civicrm Membership ID'),
      '#default_value' => $this->configuration['civi_membership_id'],
      '#description' => $this->t('Compare Membership status.<br>
      If Civicrm <code>Membership ID</code> is empty, the id is searched in the entity returned by the event.<br>
      Tokens <code>civicrm_membership:id</code>, <code>civicrm_membership:status_label</code>
      <code>civicrm_membership:status_id</code> and <code>civicrm_membership:status_id</code> are created'),
    ];



    $form['civi_membership_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Select status'),
      '#eca_token_select_option' => true,
      // #multiple not treated in bpmn.io
      // '#size' => 4,
      // '#multiple' => true,
      '#options' => $this->options,
      '#default_value' => $this->configuration['civi_membership_status'],
      '#required' => true,
      '#description' => $this->t('Membership status'),
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
