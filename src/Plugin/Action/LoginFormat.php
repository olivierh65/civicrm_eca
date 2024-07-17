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
 *   id = "civicrm_eca_login_format",
 *   label = @Translation("Format Login"),
 *   description = @Translation("format a login name according to a selected format for a CiviCRM contact.<br>"),
 *   eca_version_introduced = "1.0.0" * )
 */
class LoginFormat extends ConfigurableActionBase {

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
  public function execute(): void {
    
    $entity = $this->tokenService->getTokenData('entity');
    if (!$entity instanceof \Drupal\civicrm_entity\Entity\CivicrmEntity) {
      throw new \InvalidArgumentException(sprintf("Given entity %s is not supported.", $entity->entityTypeId ?? "is not a Civicrm_entity"));
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

      $contacts = \Civi\Api4\Contact::get(FALSE)
        ->addSelect('first_name', 'last_name', 'email_primary.email')
        ->addWhere('id', '=', $civi_contact_id)
        ->execute();
      if ($contacts->count() == 1) {
        $contact = $contacts->first();

        switch ($this->configuration['civi_login_format']) {
          case 'firstdotlast':
            $login = $this->prepare_string($contact['first_name']) .
              '.' .
              $this->prepare_string($contact['last_name']);
            break;
          case 'firstlast':
            $login = $this->prepare_string($contact['first_name']) .
              $this->prepare_string($contact['last_name']);
            break;
          case 'initialDotLast':
            $login = $this->prepare_string($contact['first_name'])[0] .
              '.' .
              $this->prepare_string($contact['last_name']);
            break;
          default:
            $login = null;
        }
        $this->tokenService->addTokenData('civicrm_user:contact_id', $civi_contact_id);
        //$this->tokenService->addTokenData('civicrm_eca:' . $this->configuration['result_token'], $login);
        $this->tokenService->addTokenData($this->configuration['result_token'], $login);
        $this->tokenService->addTokenData('civicrm_user:first_name', $contact['first_name']);
        $this->tokenService->addTokenData('civicrm_user:last_name', $contact['last_name']);
        $this->tokenService->addTokenData('civicrm_email:primary', $contact['email_primary.email']);
      }
    } catch (\Exception $e) {
      $this->logger->error('Civicrm API Excepton in civicrm_eca_login_format.Line ' . 
     $e->getLine() . ', Code ' . $e->getCode() . ', Message ' . $e->getMessage());
     throw new \InvalidArgumentException(sprintf("Error retreiving contact information"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'civi_contact_id' => '',
      'civi_login_format' => '',
      'result_token' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    
    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Tokens <code>civicrm_user:contact_id</code>,
          <code>civicrm_user:first_name</code>,
          <code>civicrm_user:last_name</code>,
          <code>civicrm_email:primary</code> are created'),
          '#weight' => 10,
    ];
    $form['civi_contact_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Civicrm contact ID'),
      '#default_value' => $this->configuration['civi_contact_id'],
      '#description' => $this->t('If Civicrm <code>Contact ID</code> is empty, the <code>contact_id</code> is searched in the entity (type <code>CivicrmEntity</code>) returned by the event.'),
    ];
    $form['civi_login_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Select format'),
      '#size' => 1,
      '#options' => [
        'firstdotlast' => 'FirstDotLast',
        'firstlast' => 'FirstLast',
        'initialDotLast' => 'InitialDotLAst',
      ],
      '#default_value' => $this->configuration['civi_login_format'],
      '#required' => true,
      '#description' => $this->t('The login name format'),
    ];

    $form['result_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Result token'),
      '#default_value' => $this->configuration['result_token'],
      '#required' => true,
      '#description' => $this->t('Provide the name of a token that holds the generated login name. Please provide the token name only, without brackets.
'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['civi_contact_id'] = $form_state->getValue('civi_contact_id');
    $this->configuration['civi_login_format'] = $form_state->getValue('civi_login_format');
    $this->configuration['result_token'] = $form_state->getValue('result_token');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Converts accentuated characters (àéïöû etc.) 
   * to their ASCII equivalent (aeiou etc.)
   * Code from https://dev.to/bdelespierre/convert-accentuated-character-to-their-ascii-equivalent-in-php-3kf1
   * 
   * @param  string $str
   * @param  string $charset
   * @return string
   */
  private function accent2ascii(string $str, string $charset = 'utf-8'): string {
    $str = htmlentities($str, ENT_NOQUOTES, $charset);

    $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
    $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères

    return $str;
  }

  private function prepare_string(string $str): string {
    return str_replace(
      ' ',
      '-',
      strtolower(
        $this->accent2ascii($str)
      )
    );
  }
}
