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
    public static function retrieveAccount(\Drupal\user\UserInterface $user) {
        $killbill_account = new Killbill_Account();
        $killbill_account->externalKey = $user->id();
        return $killbill_account->get();
    }

    /**
     * Initialize client.
     *
     * @param type $settings
     * @return boolean
     */
    public static function clientInitialize($settings = NULL) {
        // If no settings array was given, use the default account settings.
        if (empty($settings)) {
            $settings = array(
                'serverUrl' => \Drupal::config('killbill.settings')->get('killbill_server_url'),
                'apiUser' => \Drupal::config('killbill.settings')->get('killbill_tenant_api_user'),
                'apiPassword' => \Drupal::config('killbill.settings')->get('killbill_tenant_api_password'),
            );
        }

        $path = libraries_get_path('killbill-client-php');
        if ($path && file_exists(DRUPAL_ROOT . '/' . $path . '/lib/killbill.php')) {
            require_once DRUPAL_ROOT . '/' . $path . '/lib/killbill.php';
            \Killbill_Client::$serverUrl = $settings['serverUrl'];
            \Killbill_Client::$apiUser = $settings['apiUser'];
            \Killbill_Client::$apiPassword = $settings['apiPassword'];

            \Drupal::logger('killbill')->info('Successfully registered the Killbill PHP library.', array());
            return TRUE;
        } else {
            \Drupal::logger('killbill')->error('Could not find the Killbill PHP client library in libraries/killbill.', array());
            return FALSE;
        }
    }

}
