<?php

/**
 * @file
 * Contains \Drupal\killbill\Form\KillbillSettingsForm.
 */

namespace Drupal\killbill\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class KillbillSettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'killbill_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('killbill.settings');

        foreach (Element::children($form) as $variable) {
            $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
        }
        $config->save();

        if (method_exists($this, '_submitForm')) {
            $this->_submitForm($form, $form_state);
        }

        parent::submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['killbill.settings'];
    }

    public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
        $form['account'] = [
            '#type' => 'fieldset',
            '#title' => t('Default Killbill settings'),
            '#description' => t('Configure these settings based on your Killbill configuration.'),
            '#collapsible' => TRUE,
        ];
        $form['account']['killbill_server_url'] = [
            '#type' => 'textfield',
            '#title' => t('URI'),
            '#description' => t("The absolute URI of the Killbill server, e.g. http://127.0.0.1:8080."),
            '#default_value' => \Drupal::config('killbill.settings')->get('killbill_server_url'),
        ];
        $form['account']['killbill_tenant_api_key'] = [
            '#type' => 'textfield',
            '#title' => t('Tenant api key'),
            '#description' => t("Your Killbill api key"),
            '#default_value' => \Drupal::config('killbill.settings')->get('killbill_tenant_api_key'),
        ];
        $form['account']['killbill_tenant_api_secret'] = [
            '#type' => 'textfield',
            '#title' => t('Tenant api secret'),
            '#description' => t("Your Killbill api secret"),
            '#default_value' => \Drupal::config('killbill.settings')->get('killbill_tenant_api_secret'),
        ];

        $form['push'] = [
            '#type' => 'fieldset',
            '#title' => t('Push notification settings'),
            '#description' => t("Killbill can be configured to send notifications, e.g. when a new account is created or when a subscription event occurs. By default, this open-source module will listen to these " . "notifications to mirror accounts and subscriptions information in your current Drupal database: this is especially useful if you're running a hosted version of Killbill, so all " . "of your critical business data is directly available, without requiring an extra data transfer process." . "Other Drupal modules can also react to incoming push notifications by implementing  hook_killbill_process_push_notification(). This can be useful to extend this module and implement " . "business logic reactions specific to your app." . "Note: if you have supplied an HTTP authentication username and password in your Push Notifications settings in Killbill, your web server must be configured to validate these credentials " . "at your listener URL."),
            '#collapsible' => TRUE,
        ];
        $form['push']['killbill_listener_key'] = array(
            '#type' => 'textfield',
            '#title' => t('Listener URL key'),
            '#description' => t('Custom (private) URI to which Killbill will send notifications to.') . '<br />' .
            t('Based on your current key, you should set @url as your Push Notification URL at Killbill.', array('@url' => Url::fromRoute('killbill.process_push_notification', array(
                    'key' => \Drupal::config('killbill.settings')->get('killbill_listener_key')
                        ), array('absolute' => TRUE)))),
            '#default_value' => \Drupal::config('killbill.settings')->get('killbill_listener_key'),
            '#required' => TRUE,
            '#size' => 32,
            '#field_prefix' => Url::fromRoute('killbill.process_push_notification', array(
                'key' => '%'
                    ), array('absolute' => TRUE)),
        );

        $form['push']['killbill_push_logging'] = [
            '#type' => 'checkbox',
            '#title' => t('Log authenticated incoming push notifications (primarily used for debugging purposes).'),
            '#default_value' => \Drupal::config('killbill.settings')->get('killbill_push_logging'),
        ];

        return parent::buildForm($form, $form_state);
    }

}
