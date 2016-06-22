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
   * Get Products.
   *
   * @return array
   */
  public function getProducts() {
    $catalog = new \Killbill_Catalog;
    $catalog->initialize($this->tenant->getTenantHeaders());

    return $catalog->getBaseProducts();
  }

  /**
   * Update catalog
   * @global string $base_root
   * @param array $data
   */
  public function setCatalog($data) {
    $catalogModel = new \Killbill_CatalogModel();
    $catalogModel->schema = 'http://docs.killbill.io/0.16/catalog.xsd';
    $catalogModel->effectiveDate = time();
    $catalogModel->catalogName = $data['catalogName'];
    $catalogModel->recurringBillingMode = $data['recurringBillingMode'];
    foreach ($data['currencies'] as $currency) {
      $catalogModel->currencies[] = new \Killbill_CatalogModel_Currency($currency);
    }
    foreach ($data['products'] as $product) {
      $killbillProduct = new \Killbill_CatalogModel_Product($product);
      $catalogModel->products[$killbillProduct->getId()] = $killbillProduct;
    }

    foreach ($data['plans'] as $plan) {
      $productId = \Killbill_CatalogModel_Helpers::strToId($plan['product']);
      if (!empty($plan['finalPhase']['recurring'])) {
        $recurring = new \Killbill_CatalogModel_PlanPhase_Recurring(
            $plan['finalPhase']['recurring']['billingPeriod']
            , new \Killbill_CatalogModel_Price(
            $plan['finalPhase']['recurring']['recurringPrice']['currency']
            , $plan['finalPhase']['recurring']['recurringPrice']['value']
        ));
      }
      else {
        $recurring = null;
      }
      $killbillPlan = new \Killbill_CatalogModel_Plan(
          $plan['name']
          , $catalogModel->products[$productId]
          , new \Killbill_CatalogModel_PlanPhase(
          $plan['finalPhase']['type']
          , new \Killbill_CatalogModel_PlanPhase_Duration(
          $plan['finalPhase']['duration']['unit'])
          , $recurring));
      $catalogModel->plans[$killbillPlan->getId()] = $killbillPlan;
    }

    foreach ($data['priceLists'] as $priceList) {
      $plans = [];
      foreach ($priceList['plans'] as $plan) {
        $planId = \Killbill_CatalogModel_Helpers::strToId($plan);
        $plans[] = $catalogModel->plans[$planId];
      }
      $catalogModel->priceLists[] = new \Killbill_CatalogModel_PriceList(
          $priceList['name']
          , $plans
          , $priceList['type']);
    }

//    dpm($catalogModel);
//    dpm(htmlspecialchars($catalogModel->toDOM()->saveXML()));

    $errors = $catalogModel->validate();
    if (!empty($errors)) {
      drupal_set_message("Catalog is invalid.", 'error');
      foreach ($errors as $error) {
        $m = t('XML error "!message" [!level] (Code !code) in !file on line !line column !column', array(
          '!message' => $error->message,
          '!level' => $error->level,
          '!code' => $error->code,
          '!file' => $error->file,
          '!line' => $error->line,
          '!column' => $error->column,
        ));
        drupal_set_message($m, 'error');
      }
    }
    else {
      global $base_root;
      $catalog = new \Killbill_Catalog;
      $catalog->xmlDOM = $catalogModel->toDOM();
      /* @var $response \Killbill_Response */
      $response = $catalog->setFullCatalog($base_root
          , 'DRUPAL'
          , "DRUPAL_UPDATE_CATLOG::" . \Drupal::request()->getClientIp()
          , $this->tenant->getTenantHeaders());

      if ($response->statusCode == 201) {
        drupal_set_message('Updated catalog success');
      }
    }
  }

}
