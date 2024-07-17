<?php

namespace Drupal\civicrm_eca\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;
use Drupal\eca_content\Event\ContentEntityPreSave;
use Drupal\eca\Attributes\Token;

use Drupal\user\Entity\User;
use \Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\civicrm\Civicrm;





/**
 * Plugin implementation of the ECA condition "Drupal User Exist".
 *
 * @EcaCondition(
 *   id = "civicrm_eca_drupal_user_exist",
 *   label = @Translation("Drupal User Exist"),
 *   description = @Translation("CiviCRM Contact linked User exists."),
 *   eca_version_introduced = "1.0.0",
 * )
 */
class DrupalUserExistCondition extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $entity = $this->tokenService->getTokenData('entity');
    if (!$entity instanceof \Drupal\civicrm_entity\Entity\CivicrmEntity) {
      return $this->negationCheck(false);
    }

    //retreive the contact_id
    if (!empty($this->configuration['civi_contact_id'])) {
      $civi_contact_id = $this->configuration['civi_contact_id'];
    } else {
      if ((!$entity instanceof \Drupal\civicrm_entity\Entity\CivicrmEntity) && (!$entity->hasField('contact_id'))) {
        throw new \InvalidArgumentException(sprintf("Given entity %s is not supported.", $entity->entityTypeId ?? "is not a Civicrm_entity"));
      }
      //retreive the membership_id
      $civi_contact_id = $entity->get('contact_id')->getString();
    }


    try {
      // Perfom civicrm bootstrap
      \Drupal::service('civicrm')->initialize();
      
      // get uf_id
      $uFMatches = \Civi\Api4\UFMatch::get(FALSE)
        ->addSelect('uf_id', 'uf_name')
        ->addWhere('contact_id', '=', $civi_contact_id)
        ->execute();


      if ($uFMatches->count() == 0) {
        return $this->negationCheck(false);
      }

      $drupal_account = User::load($uFMatches->first()['uf_id']);
      if (is_object($drupal_account)) {
        $this->tokenService->addTokenData('civicrm_uf:uf_id', $uFMatches->first()['uf_id']);
        $this->tokenService->addTokenData('civicrm_uf:uf_name', $uFMatches->first()['uf_name']);

        return $this->negationCheck(true);
      }
    } catch (\Exception $e) {
      return $this->negationCheck(FALSE);
    }
    return $this->negationCheck(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'civi_contact_id' => '',
      'civi_uf_id' => '',
      'civi_uf_name' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Tokens <code>civicrm_uf:uf_id</code> (Drupal user ID) and <code>civicrm_uf:uf_name</code> (Drupal login name) are created.<br>'),
      '#weight' => 10,
    ];
    $form['civi_contact_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Civicrm Contact ID'),
      '#default_value' => $this->configuration['civi_contact_id'],
      '#eca_token_reference' => true,
      '#description' => $this->t('Check if a drupal user exist.
      If Civicrm <code>Contact ID</code> is empty, the <code>contact_id</code> is searched in the entity (type <code>CivicrmEntity</code>) returned by the event.'),
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
