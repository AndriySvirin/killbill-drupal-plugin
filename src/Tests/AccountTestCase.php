<?php

/**
 * @file
 * Contains \Drupal\killbill\Tests\AccountTestCase.
 */

namespace Drupal\killbill\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests account.
 *
 * @group killbill
 */
class AccountTestCase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('killbill');

  /**
   * Test various behaviors for anonymous users.
   */
  function testCreateAccount() {
    $user_config = $this->container->get('config.factory')->getEditable('user.settings');
    $user_config
        ->set('verify_mail', FALSE)
        ->set('register', USER_REGISTER_VISITORS)
        ->save();
    // Try to register a user.
    $name_suffix = rand(1, 100);
    $pass = $this->randomString(10);
    $register = array(
      'mail' => 'name-' . $name_suffix . '@example.com',
      'name' => 'name_' . $name_suffix,
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
      'killbill_account[killbill_name]' => 'Test name ' . $name_suffix,
      'killbill_account[killbill_address1]' => 'Test Address 1 ' . $name_suffix,
      'killbill_account[killbill_address2]' => 'Test Address 2 ' . $name_suffix,
      'killbill_account[killbill_company_name]' => 'Test Company name ' . $name_suffix,
      'killbill_account[killbill_city]' => 'Test city ' . $name_suffix,
      'killbill_account[killbill_state_or_province]' => 'Test state or province ' . $name_suffix,
      'killbill_account[killbill_country]' => 'Test country ' . $name_suffix,
      'killbill_account[killbill_postal_code]' => '12345',
      'killbill_account[killbill_phone]' => '123456789',
    );
    $this->drupalPostForm('/user/register', $register, t('Create new account'));

    $this->assertRaw(t('Billing account was created.'), t('User properly created in killbill.'));
  }

}
