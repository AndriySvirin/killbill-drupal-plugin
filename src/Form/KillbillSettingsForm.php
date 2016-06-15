<?php

/**
 * @file
 * Contains \Drupal\killbill\Form\KillbillSettingsForm.
 */

namespace Drupal\killbill\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
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
        $config = $this->configFactory->getEditable('killbill.settings');
        $config->set('server_url', $form_state->getValue('server_url'));
        $config->set('api_user', $form_state->getValue('api_user'));
        $config->set('api_password', $form_state->getValue('api_password'));
        $config->set('listener_key', $form_state->getValue('listener_key'));
        $config->set('push_logging', $form_state->getValue('push_logging'));
        $config->save();

        parent::submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['killbill.settings'];
    }

    public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
        $config = $this->configFactory->get('killbill.settings');

        $form['account'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Default Killbill settings'),
            '#description' => $this->t('Configure these settings based on your Killbill configuration.'),
            '#collapsible' => TRUE,
        ];
        $form['account']['server_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('URI'),
            '#description' => $this->t("The absolute URI of the Killbill server, e.g. http://127.0.0.1:8080."),
            '#default_value' => $config->get('server_url'),
        ];
        $form['account']['api_user'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Killbill user'),
            '#description' => $this->t("Your Killbill user"),
            '#default_value' => $config->get('api_user'),
        ];
        $form['account']['api_password'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Killbill password'),
            '#description' => $this->t("Your Killbill password"),
            '#default_value' => $config->get('api_password'),
        ];

        $form['push'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Push notification settings'),
            '#description' => $this->t("Killbill can be configured to send notifications, e.g. when a new account is created or when a subscription event occurs. By default, this open-source module will listen to these " . "notifications to mirror accounts and subscriptions information in your current Drupal database: this is especially useful if you're running a hosted version of Killbill, so all " . "of your critical business data is directly available, without requiring an extra data transfer process." . "Other Drupal modules can also react to incoming push notifications by implementing  hook_killbill_process_push_notification(). This can be useful to extend this module and implement " . "business logic reactions specific to your app." . "Note: if you have supplied an HTTP authentication username and password in your Push Notifications settings in Killbill, your web server must be configured to validate these credentials " . "at your listener URL."),
            '#collapsible' => TRUE,
        ];
        $form['push']['listener_key'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Listener URL key'),
            '#description' => $this->t('Custom (private) URI to which Killbill will send notifications to.') . '<br />' .
            $this->t('Based on your current key, you should set @url as your Push Notification URL at Killbill.', array('@url' => Url::fromRoute('killbill.process_push_notification', array(
                    'key' => \Drupal::config('killbill.settings')->get('listener key')
                        ), array('absolute' => TRUE)))),
            '#default_value' => $config->get('listener_key'),
            '#required' => TRUE,
            '#size' => 32,
            '#field_prefix' => Url::fromRoute('killbill.process_push_notification', array(
                'key' => '%'
                    ), array('absolute' => TRUE)),
        );

        $form['push']['push_logging'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Log authenticated incoming push notifications (primarily used for debugging purposes).'),
            '#default_value' => $config->get('push_logging'),
        ];

        return parent::buildForm($form, $form_state);
    }

}
