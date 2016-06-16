<?php

/**
 * @file
 * Contains \Drupal\killbill\Controller\DefaultController.
 */

namespace Drupal\killbill\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the killbill module.
 */
class DefaultController extends ControllerBase {

  public function processPushNotification($key = null) {
    $notification = NULL;

    // Ensure the push notification was sent to the proper URL
    if ($key != \Drupal::config('killbill.settings')->get('listener_key')) {
      // Log the failed attempt and bail.
      \Drupal::logger('killbill')->warning('Incoming push notification did not contain the proper URL key.', []);
      return;
    }

    if (!Drupal\killbill\KillbillHelpers::clientInitialize()) {
      return;
    }

    // TODO - this will probably be json
    $notification = "TODO";

    // Bail if this is an empty or invalid notification
    if (empty($notification)) {
      return;
    }

    // Log the incoming push notification if enabled
    if (\Drupal::config('killbill.settings')->get('push_logging')) {
      \Drupal::logger('killbill')->notice('Incoming push notification: !notification', [
        '!notification' => '<pre>' . \Drupal\Component\Utility\SafeMarkup::checkPlain(print_r($notification, TRUE)) . '</pre>'
      ]);
    }

    \Drupal::moduleHandler()->invokeAll('killbill_process_push_notification', [
      $notification
    ]);
  }

  public function catalog() {
    if (!\Drupal\killbill\KillbillHelpers::clientInitialize()) {
      $output['status'] = array(
        '#plain_text' => t('Could not initialize the Killbill client.'),
      );
      return $output;
    }

    $catalog = new \Killbill_Catalog;
    $catalog->initialize();

    $header = [
      t('Product name'),
      t('Product type'),
      t('Plan name'),
      t('Phase type'),
      t('Price in USD'),
    ];
    $rows = [];

    $currency = 'USD';
    foreach ($catalog->getBaseProducts() as $product) {
      foreach ($product->plans as $plan) {
        foreach ($plan->phases as $phase) {
          $rows[] = [
            t('@name', ['@name' => $product->name]),
            t('@type', [
              '@type' => $product->type
            ]),
            t('@plan', ['@plan' => $plan->name]),
            t('@phase', [
              '@phase' => $phase->type
            ]),
            t('@price', [
              '@price' => '$' . number_format($phase->prices->$currency, 2)
            ]),
          ];
        }
      }
    }

    $output['catalog'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No catalog found.'),
      '#attributes' => array(
        'id' => 'my-module-table',
      ),
    );

    return $output;
  }

}
