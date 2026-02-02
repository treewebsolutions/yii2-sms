<?php

namespace tws\sms\transport;

use tws\sms\MessageInterface;
use yii\base\BaseObject;
use yii\httpclient\Client;

/**
 * Handles transport of the SMS message by using smsgateway.me API.
 *
 * @link https://smsgateway.me/
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class SmsGateway extends BaseObject implements TransportInterface
{
	/**
	 * @var string The API base URL.
	 */
	public $baseUrl;

	/**
	 * @var string The API token.
	 */
	public $token;

	/**
	 * @var int The API device ID used as a gateway.
	 */
	public $deviceId;


	/**
	 * @inheritdoc
	 */
	public function normalize($message)
	{
		$messages = $message;
		if (!is_array($messages)) {
			$messages = [$messages];
		}

		return array_map(function (MessageInterface $message) {
			return [
				'device_id' => $this->deviceId,
				'from' => $message->getFrom(),
				'phone_number' => $message->getTo(),
				'message' => $message->toString(),
			];
		}, $messages);
	}

	/**
	 * @inheritdoc
	 * @link https://smsgateway.me/sms-api-documentation/messages/sending-a-sms-message
	 */
	public function send($message)
	{
		return $this->makeRequest('/message/send','POST', $this->normalize($message));
	}

	/**
	 * @inheritdoc
	 * @link https://smsgateway.me/sms-api-documentation/messages/sending-a-sms-message
	 */
	public function sendMultiple($messages)
	{
		return $this->makeRequest('/message/send','POST', $this->normalize($messages));
	}

	/**
	 * Makes an HTTP request to the API.
	 *
	 * @param string $url
	 * @param string $method
	 * @param array $data
	 * @return mixed
	 */
	protected function makeRequest($url, $method = 'GET', $data = [])
	{
		try {
			$client = new Client([
				'transport' => 'yii\httpclient\CurlTransport',
				'baseUrl' => $this->baseUrl,
				'requestConfig' => [
					'format' => Client::FORMAT_JSON,
				],
				'responseConfig' => [
					'format' => Client::FORMAT_JSON,
				],
			]);

			/** @var \yii\httpclient\Response $response */
			$response = $client->createRequest()
				->addHeaders(['Authorization' => $this->token])
				->setMethod($method)
				->setUrl($url)
				->setData($data)
				->send();

			return $response->isOk ? $response->data : false;
		} catch (\Exception $e) {
			return false;
		}
	}
}
