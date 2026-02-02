<?php

namespace tws\sms\transport;

use tws\sms\MessageInterface;
use yii\base\BaseObject;
use yii\httpclient\Client;

/**
 * Handles transport of the SMS message by using voxline.ro API.
 *
 * @link http://voxline.ro/
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class Voxline extends BaseObject implements TransportInterface
{
	/**
	 * @var string The API base URL.
	 */
	public $baseUrl;

	/**
	 * @var string The API username.
	 */
	public $username;

	/**
	 * @var string The API password.
	 */
	public $password;


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
				'telefon' => $message->getTo(),
				'mesaj' => $message->toString(),
				'uuid' => uniqid(),
			];
		}, $messages);
	}

	/**
	 * @inheritdoc
	 */
	public function send($message)
	{
		return $this->makeRequest('','POST', reset($this->normalize($message)));
	}

	/**
	 * @inheritdoc
	 */
	public function sendMultiple($messages)
	{
		$count = 0;

		foreach ($messages as $message) {
			$this->send($message);
			$count++;
		}

		return $count;
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
				->addHeaders(['Authorization' => 'Basic ' . base64_encode("{$this->username}:{$this->password}")])
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
