<?php

namespace Drupal\killbill;

/**
 * Helpers functions.
 */
class KillbillHelpers {

  /**
   * Retrive account.
   * @param \Drupal\user\UserInterface $user
   * @return type
   */
  static function retrieveAccount(\Drupal\user\UserInterface $user) {
    if (!self::clientInitialize()) {
      return;
    }
    $killbill_account = new \Killbill_Account();
    $killbill_account->externalKey = $user->id();
    return $killbill_account->get();
  }

  /**
   * Initialize client.
   *
   * @param type $settings
   * @return boolean
   */
  static function clientInitialize($settings = NULL) {
    // If no settings array was given, use the default account settings.
    if (empty($settings)) {
      $settings = array(
        'server_url' => \Drupal::config('killbill.settings')->get('server_url'),
        'api_user' => \Drupal::config('killbill.settings')->get('api_user'),
        'api_password' => \Drupal::config('killbill.settings')->get('api_password'),
      );
    }

    require_once DRUPAL_ROOT . '/vendor/killbill/killbill-client-php/lib/killbill.php';

    \Killbill_Client::$serverUrl = $settings['server_url'];
    \Killbill_Client::$apiUser = $settings['api_user'];
    \Killbill_Client::$apiPassword = $settings['api_password'];

    return TRUE;
  }

  /**
   * Create account
   * @global string $base_root
   * @param \Drupal\user\UserInterface $account
   * @return boolean
   */
  static function createAccount(\Drupal\user\UserInterface $account) {
    if (!self::clientInitialize()) {
      return FALSE;
    }

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
    $accountData->length = strlen($accountData->name);
    if (property_exists($account, 'timezone')) {
      $accountData->timeZone = $account->timezone;
    }

    global $base_root;
    $accountData->create($base_root, "DRUPAL", "DRUPAL_HOOK_USER_INSERT::" . \Drupal::request()->getClientIp());
  }

}