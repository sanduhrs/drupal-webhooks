<?php

namespace Drupal\webhooks\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class webhookForm.
 *
 * @package Drupal\webhooks\Form
 */
class webhookForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $webhook = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $webhook->label(),
      '#description' => $this->t("Label for the Webhook."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $webhook->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\webhooks\Entity\webhook::load',
      ),
      '#disabled' => !$webhook->isNew(),
    );

    /* You will need additional form elements for your custom properties. */
    $form['payload_url']= array(
      '#type' => 'textfield',
      '#title' => $this->t('Payload URL'),
        '#attributes' => array(
            'placeholder' => $this->t('http://www.example.com'),
        ),
      '#maxlength' => 255,
      '#description' => $this->t("Target URL for your payload."),
      '#required' => TRUE,
    );

    $form['secret'] = array(
      '#type' => 'password_confirm',
        '#attributes' => array(
            'placeholder' => $this->t('My placeholder text'),
        ),
      '#title' => $this->t('Secret'),
      '#maxlength' => 255,
      '#description' => $this->t("Password to scramble your webhook."),
      '#required' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $webhook = $this->entity;
    $status = $webhook->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Webhook.', [
          '%label' => $webhook->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Webhook.', [
          '%label' => $webhook->label(),
        ]));
    }
    $form_state->setRedirectUrl($webhook->urlInfo('collection'));
  }

}
