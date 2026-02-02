<?php

namespace tws\sms\transport;

use tws\sms\MessageInterface;
use yii\base\BaseObject;
use yii\httpclient\Client;

/**
 * Handles transport of the SMS message by using bulksms.com API.
 *
 * @link https://www.bulksms.com/
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class BulkSms extends BaseObject implements TransportInterface
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
	 * @inheritdoc
	 */
	public function normalize($message)
	{
		$messages = $message;
		if (!is_array($messages)) {
			$messages = [$messages];
		}

		return array_map(function (MessageInterface $message) {
			$to = preg_replace('/\s+/', '', $message->getTo());
			if (mb_substr(trim($to), 0, 1) !== '+') {
				$to = "+4{$to}";
			}
			return [
				'from' => $message->getFrom(),
				'to' => $to,
				'body' => $message->toString(),
				'encoding' => 'UNICODE',
			];
		}, $messages);
	}

	/**
	 * @inheritdoc
	 * @link https://www.bulksms.com/developer/json/v1/#tag/Message%2Fpaths%2F~1messages%2Fpost
	 */
	public function send($message)
	{
		return $this->makeRequest('/messages','POST', $this->normalize($message));
	}

	/**
	 * @inheritdoc
	 * @link https://www.bulksms.com/developer/json/v1/#tag/Message%2Fpaths%2F~1messages%2Fpost
	 */
	public function sendMultiple($messages)
	{
		return $this->makeRequest('/messages','POST', $this->normalize($messages));
	}

	/**
	 * Makes an HTTP request to the API.
	 *
	 * @link https://www.bulksms.com/developer/json/v1/#section/Authentication
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
				->addHeaders(['Authorization' => 'Basic ' . base64_encode($this->token)])
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
