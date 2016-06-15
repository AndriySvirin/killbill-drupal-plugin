<?php

/**
 * Processes an incoming push notification.
 *
 * Killbill can be configured to send notifications, e.g. when a new account is created
 * or when a subscription event occurs.
 *
 * By default, this open-source module will listen to these notifications to
 * mirror accounts and subscriptions information in the Drupal database.
 * Other modules can react to incoming push notifications by implementing
 * hook_killbill_process_push_notification(). This can be useful to extend this module
 * and implement business logic reactions specific to your app.
 */
function hook_killbill_process_push_notification(&$notification) {

}
