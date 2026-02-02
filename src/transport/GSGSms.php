<?php

namespace tws\sms\transport;

use tws\sms\MessageInterface;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client;

/**
 * Handles transport of the SMS message by using smsgateway API.
 *
 * @link https://www.gestiuneservice.ro/smsgateway
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class GSGSms extends BaseObject implements TransportInterface
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
            $to = preg_replace('/\s+/', '', $message->getTo());
            if (mb_substr(trim($to), 0, 1) !== '+') {
                $to = "+4{$to}";
            }
            return [
                'number' =>  $to,
                'message' => $message->toString(),
                'devices' => $this->deviceId,
                'key' => $this->token,
            ];
        }, $messages);
    }

    /**
     * @inheritdoc
     * @link https://www.gestiuneservice.ro/smsgateway/api.php
     */
    public function send($message)
    {
        $data = $this->normalize($message)[0];
        return $this->makeRequest('/services/send.php','POST', $data);
    }

    /**
     * @inheritdoc
     * @link https://www.gestiuneservice.ro/smsgateway/api.php
     */
    public function sendMultiple($messages)
    {
        $dataMessages = []; $dataDevices = [];
        $records = $this->normalize($messages);
        foreach ($records as $record) {
            $dataMessages[] = [
                'number' => $record['number'],
                'message' => $record['message'],
            ];
            $dataDevices[] = $record['devices'];
        }
        $data = [
            'messages' => json_encode($dataMessages),
            'devices' => json_encode(array_unique($dataDevices)),
            'key' => $this->token,
        ];
        return $this->makeRequest('/services/send.php','POST', $data);
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
            ]);

            /** @var \yii\httpclient\Response $response */
            $response = $client->createRequest()
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
