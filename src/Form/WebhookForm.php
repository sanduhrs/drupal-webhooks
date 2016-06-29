<?php

namespace Drupal\webhooks\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class webhookForm.
 *
 * @package Drupal\webhooks\Form
 */
class WebhookForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webhooks\Entity\Webhook $webhook */
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

    $form['payload_url']= array(
      '#type' => 'textfield',
      '#title' => $this->t('Payload URL'),
      '#attributes' => array(
        'placeholder' => $this->t('http://example.com/post'),
      ),
      '#default_value' => $webhook->getPayloadUrl(),
      '#maxlength' => 255,
      '#description' => $this->t("Target URL for your payload."),
      '#required' => TRUE,
    );

    $form['secret'] = array(
      '#type' => 'textfield',
      '#attributes' => array(
        'placeholder' => $this->t('Secret'),
      ),
      '#title' => $this->t('Secret'),
      '#maxlength' => 255,
      '#description' => $this->t("Secret that the target website gave you."),
    );

    $form['active'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Active"),
      '#description' => $this->t("Shows if the webhook is active or not."),
      '#default_value' => TRUE,
    );

    $form['content_type'] = array(
      '#type' => 'select',
      '#titel' => $this->t("Content Type"),
      '#description' => $this->t("The Content Type of your webhook."),
      '#options' => [
        'json' => $this->t('application/json'),
        'xml' => $this->t('application/xml'),
        'form' => $this->t('application/x-www-form-urlencoded'),
      ],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $webhook = $this->entity;
    $active = $webhook->save();

    switch ($active) {
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
