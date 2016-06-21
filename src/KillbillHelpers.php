<?php

namespace Drupal\killbill;

use Drupal\user\UserInterface;

/**
 * Helpers functions.
 */
class KillbillHelpers {

  /**
   * @type \Killbill_Tenant.
   */
  public $tenant;

  /**
   * Constructor
   */
  public function __construct() {
    $this->clientInitialize();

    $this->tenant = new \Killbill_Tenant();
    $this->tenant->apiKey = \Drupal::config('killbill.settings')->get('api_key');
    $this->tenant->apiSecret = \Drupal::config('killbill.settings')->get('api_secret');
  }

  /**
   * Retrive account.
   * @param \Drupal\user\UserInterface $user
   * @return \Killbill_Account
   */
  public function retrieveAccount(UserInterface $user) {
    $killbill_account = new \Killbill_Account();
    $killbill_account->externalKey = $user->id();
    $account = $killbill_account->get($this->tenant->getTenantHeaders());
    return $account;
  }

  /**
   * Initialize client.
   *
   * @return boolean
   */
  public function clientInitialize() {
    require_once DRUPAL_ROOT . '/vendor/killbill/killbill-client-php/lib/killbill.php';

    \Killbill_Client::$serverUrl = \Drupal::config('killbill.settings')->get('server_url');
    \Killbill_Client::$apiUser = \Drupal::config('killbill.settings')->get('admin_user');
    \Killbill_Client::$apiPassword = \Drupal::config('killbill.settings')->get('admin_password');
  }

  /**
   * Create account
   * @global string $base_root
   * @param \Drupal\user\UserInterface $account
   * @return boolean
   */
  public function createAccount(UserInterface $account) {
    $accountData = new \Killbill_Account();
    $accountData->externalKey = $account->id();
    $accountData->name = $account->getUsername();
    $accountData->email = $account->getEmail();
    $accountData->currency = 'USD';
    $accountData->paymentMethodId = null;
    $accountData->address1 = null;
    $accountData->address2 = null;
    $accountData->company = null;
    $accountData->state = null;
    $accountData->country = null;
    $accountData->phone = null;
    $accountData->firstNameLength = strlen($accountData->name);
    if (property_exists($account, 'timezone')) {
      $accountData->timeZone = $account->timezone;
    }

    global $base_root;
    $responseAccountData = $accountData->create($base_root
        , "DRUPAL"
        , "DRUPAL_HOOK_USER_INSERT::" . \Drupal::request()->getClientIp()
        , $this->tenant->getTenantHeaders());

    if (is_object($responseAccountData) && $responseAccountData->accountId != null) {
      return true;
    }

    return false;
  }

  /**
   * Update account
   * @global string $base_root
   * @param \Drupal\user\UserInterface $user
   * @param array $attributes
   * @return boolean
   */
  public function updateAccount(UserInterface $user, array $attributes = null) {
    $currentAccount = $this->retrieveAccount($user);
    foreach ($attributes as $attribute => $value) {
      $currentAccount->{$attribute} = $value;
    }
    global $base_root;
    $currentAccount->update($base_root
        , 'DRUPAL'
        , "DRUPAL_HOOK_FORM_USER_REGISTER_FORM_SUBMIT::" . \Drupal::request()->getClientIp()
        , $this->tenant->getTenantHeaders());
  }

  /**
   * Push products
   */
  public function pushProducts() {

  }

}
