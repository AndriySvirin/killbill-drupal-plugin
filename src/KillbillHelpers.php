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
    $response = $accountData->create($base_root
        , "DRUPAL"
        , "DRUPAL_HOOK_USER_INSERT::" . \Drupal::request()->getClientIp()
        , $this->tenant->getTenantHeaders());

    if ($response instanceof \Killbill_Account) {
      return true;
    }
    else {
      \Drupal::logger('killbill')->notice(json_encode($response));
      return false;
    }
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
   *
   * @global string $base_root
   * @param array $data
   *
   * @return array
   */
  public function setCatalog($data) {
    $output = [];
    $catalogModel = new \Killbill_CatalogModel();
    $catalogModel->schema = 'http://docs.killbill.io/0.16/catalog.xsd';
    $catalogModel->effectiveDate = time();
    $catalogModel->catalogName = $data['catalogName'];
    $catalogModel->recurringBillingMode = $data['recurringBillingMode'];
    $this->_setCatalogCurrencies($catalogModel, $data['currencies']);
    if (!empty($data['units'])) {
      $this->_setCatalogUnits($catalogModel, $data['units']);
    }
    $this->_setCatalogProducts($catalogModel, $data['products']);
    $this->_setCatalogPlans($catalogModel, $data['plans']);
    $this->_setCatalogPriceLists($catalogModel, $data['priceLists']);

    $errors = $catalogModel->validate();
    if (!empty($errors)) {
      $output[] = 'Catalog is invalid.';
      foreach ($errors as $error) {
        $output[] = t('XML error "!message" [!level] (Code !code) in !file on line !line column !column .', array(
          '!message' => $error->message,
          '!level' => $error->level,
          '!code' => $error->code,
          '!file' => $error->file,
          '!line' => $error->line,
          '!column' => $error->column,
        ));
      }
    }
    else {
      global $base_root;
      $catalog = new \Killbill_Catalog;
      /* @var $response \Killbill_Response */
      $response = $catalog->setFullCatalog(
          $catalogModel->toDOM()
          , $base_root
          , 'DRUPAL'
          , "DRUPAL_UPDATE_CATLOG::" . \Drupal::request()->getClientIp()
          , $this->tenant->getTenantHeaders());

      if (!$response->statusCode == 201) {
        $output[] = 'Updated catalog fail.';
      }
    }

    return $output;
  }

  /**
   * Set currencies
   *
   * @param \Killbill_CatalogModel $catalogModel
   * @param array $currencies
   */
  private function _setCatalogCurrencies(\Killbill_CatalogModel $catalogModel, array $currencies) {
    foreach ($currencies as $currency) {
      $catalogModel->currencies[] = new \Killbill_CatalogModel_Currency($currency);
    }
  }

  /**
   * Set units
   *
   * @param \Killbill_CatalogModel $catalogModel
   * @param array $units
   */
  private function _setCatalogUnits(\Killbill_CatalogModel $catalogModel, array $units) {
    foreach ($units as $unit) {
      $killbillUnit = new \Killbill_CatalogModel_Unit($unit);
      $catalogModel->units[$killbillUnit->getId()] = $killbillUnit;
    }
  }

  /**
   * Set products
   *
   * @param \Killbill_CatalogModel $catalogModel
   * @param array $products
   */
  private function _setCatalogProducts(\Killbill_CatalogModel $catalogModel, array $products) {
    foreach ($products as $product) {
      $killbillProduct = new \Killbill_CatalogModel_Product($product);
      $catalogModel->products[$killbillProduct->getId()] = $killbillProduct;
    }
  }

  /**
   * Set plans
   *
   * @param \Killbill_CatalogModel $catalogModel
   * @param array $plans
   */
  private function _setCatalogPlans(\Killbill_CatalogModel $catalogModel, array $plans) {
    foreach ($plans as $plan) {
      $productId = \Killbill_CatalogModel_Helpers::strToId($plan['product']);
      $recurring = !empty($plan['finalPhase']['recurring']) ? $this->_setCatalogPlansGetRecurring($plan['finalPhase']['recurring']) : null;
      $usages = !empty($plan['finalPhase']['usages']) ? $this->_setCatalogPlansGetUsages($catalogModel, $plan['finalPhase']['usages']) : null;
      $killbillPlan = new \Killbill_CatalogModel_Plan(
          $plan['name']
          , $catalogModel->products[$productId]
          , new \Killbill_CatalogModel_PlanPhase(
          $plan['finalPhase']['type']
          , new \Killbill_CatalogModel_PlanPhase_Duration(
          $plan['finalPhase']['duration']['unit'])
          , $recurring, $usages));
      $catalogModel->plans[$killbillPlan->getId()] = $killbillPlan;
    }
  }

  /**
   * Get recurring
   *
   * @return \Killbill_CatalogModel_PlanPhase_Recurring
   */
  private function _setCatalogPlansGetRecurring($recurring) {
    $recurringPrice = !empty($recurring['recurringPrice']) ? $this->_setCatalogPlansGetUsagesGetRecurringPrice($recurring['recurringPrice']) : null;

    $output = new \Killbill_CatalogModel_PlanPhase_Recurring(
        $recurring['billingPeriod']
        , $recurringPrice);

    return $output;
  }

  /**
   * Get usages
   *
   * @param \Killbill_CatalogModel $catalogModel
   * @param array $usages
   *
   * @return \Killbill_CatalogModel_PlanPhase_Usage[]
   */
  private function _setCatalogPlansGetUsages(\Killbill_CatalogModel $catalogModel, array $usages) {
    $output = [];
    foreach ($usages as $usage) {
      $usageId = \Killbill_CatalogModel_Helpers::strToId($usage['name']);
      $limits = !empty($usage['limits']) ? $this->_setCatalogPlansGetUsagesGetLimits($catalogModel, $usage['limits']) : null;
      $recurringPrice = !empty($usage['recurringPrice']) ? $this->_setCatalogPlansGetUsagesGetRecurringPrice($usage['recurringPrice']) : null;
      $output[$usageId] = new \Killbill_CatalogModel_PlanPhase_Usage(
          $usage['name']
          , $usage['billingMode']
          , $usage['usageType']
          , isset($usage['billingPeriod']) ? $usage['billingPeriod'] : null
          , $limits
          , $recurringPrice
      );
    }

    return $output;
  }

  /**
   * Get limits
   *
   * @param \Killbill_CatalogModel $catalogModel
   * @param array $limits
   *
   * @return array
   */
  private function _setCatalogPlansGetUsagesGetLimits(\Killbill_CatalogModel $catalogModel, array $limits) {
    $output = [];
    foreach ($limits as $limit) {
      $unitId = \Killbill_CatalogModel_Helpers::strToId($limit['unit']);
      $min = !empty($limit['min']) ? $limit['min'] : null;
      $max = !empty($limit['max']) ? $limit['max'] : null;
      $output[] = new \Killbill_CatalogModel_Limit($catalogModel->units[$unitId], $min, $max);
    }

    return $output;
  }

  /**
   * Get recurringPrice
   *
   * @param array $recurringPrice
   *
   * @return array
   */
  private function _setCatalogPlansGetUsagesGetRecurringPrice(array $recurringPrice) {
    $output = [];

    foreach ($recurringPrice as $price) {
      $output[] = new \Killbill_CatalogModel_Price(
          $price['currency']
          , $price['value']
      );
    }

    return $output;
  }

  /**
   * Set priceLists
   *
   * @param \Killbill_CatalogModel $catalogModel
   * @param array $priceLists
   */
  private function _setCatalogPriceLists(\Killbill_CatalogModel $catalogModel, array $priceLists) {
    foreach ($priceLists as $priceList) {
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
  }

}
