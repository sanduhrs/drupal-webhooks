<?php

namespace Drupal\webhooks;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webhooks\Entity\Webhook;
use Drupal\webhooks\Entity\WebhookInterface;
use GuzzleHttp\Client;

/**
 * Class WebhookService.
 *
 * @package Drupal\webhooks
 */
class WebhookService extends Webhook implements WebhookServiceInterface {

  /**
   * @var \Drupal::httpClient()
   */
  protected $client;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;



  /**
   * WebhookService constructor.
   *
   * @param \GuzzleHttp\Client $client
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   */
  public function __construct(
      Client $client,
      LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->client = $client;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * @param \Drupal\webhooks\Entity\Webhook $webhook
   * @param \Drupal\webhooks\Payload $payload
   */
  public function send(Webhook $webhook, Payload $payload) {
    $string = self::encode(
      $payload->getPayload(),
      $webhook->getContentType()
    );
    try {
      $this->client->post(
        $webhook->getPayloadUrl(),
        ['body' => $string]
      );
    } catch (\Exception $e) {
      // TODO: Fixme
      $this->loggerFactory->get('webhooks')->error($e->getMessage());
    }
  }

  /**
   * @param $data
   * @param $content_type
   * @return bool|string|\Symfony\Component\Serializer\Encoder\scalar
   */
  public static function encode($data, $content_type) {
    /** @var \Drupal\serialization\Encoder\JsonEncoder $encoder */
    $encoder = \Drupal::service('serializer.encoder.' . $content_type);
    if (!empty($encoder) && $encoder->supportsEncoding($content_type)) {
      return $encoder->encode($data, $content_type);
    }
    return '';
  }

  /**
   * @param $data
   * @param $content_type
   * @return bool|string|\Symfony\Component\Serializer\Encoder\scalar
   */
  public static function decode($data, $content_type) {
    /** @var \Drupal\serialization\Encoder\JsonEncoder $encoder */
    $encoder = \Drupal::service('serializer.encoder.' . $content_type);
    if (!empty($encoder) && $encoder->supportsDecoding($content_type)) {
      return $encoder->decode($data);
    }
    return false;
  }

  public function receive() {
    switch($_SERVER['CONTENT_TYPE']) {
      case 'application/json':
          \GuzzleHttp\json_decode();
        break;
      case 'application/xml':
          xmlrpc_decode();
        break;
      case 'x-www-form-urlencoded':
          urldecode();
        break;
      default :
        $this->loggerFactory->get('webhooks')
          ->error('Content-type not supported.');
        break;
    }
  }

}
