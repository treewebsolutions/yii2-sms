<?php

namespace tws\sms;

use tws\sms\transport\TransportInterface;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Sms implements an sms functionality based on different transport.
 *
 * To use Sms, you should configure it in the application configuration like the following:
 *
 * ```php
 * [
 *     'components' => [
 *         'sms' => [
 *             'class' => 'tws\sms\Sms',
 *             'transport' => [
 *             		[
 * 										'class' => 'tws\sms\transport\SmsGateway',
 * 										'deviceId' => 'DEVICE_ID',
 *                 		'baseUrl' => 'https://base.api.url/v1',
 *                 		'token' => 'API_AUTH_TOKEN',
 * 								],
 *             		[
 * 										'class' => 'tws\sms\transport\BulkSms',
 *                 		'baseUrl' => 'https://base.api.url/v1',
 *                 		'token' => 'API_AUTH_TOKEN',
 * 								],
 *             ],
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ],
 * ```
 *
 * You may also skip the configuration of the [[transport]] property. In that case, the default
 * `tws\sms\transport\FileTransport` transport will be used to send sms messages.
 *
 * To send an sms, you may use the following code:
 *
 * ```php
 * Yii::$app->sms->compose()
 *     ->setFrom('12345')
 *     ->setTo(+1234567890)
 *     ->setTextBody('Hello, This is a test message.')
 *     ->send();
 * ```
 *
 * @property TransportInterface $transport This property is read-only.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class Sms extends BaseSms
{
	/**
	 * @inheritdoc
	 */
	public $messageClass = 'tws\sms\Message';

	/**
	 * @var string the default transport class name.
	 */
	public $defaultTransportClass;

	/**
	 * @var array single or multiple transport instances with their configuration.
	 */
	private $_transport = [];


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if (!$this->getIsMultipleTransport()) {
			$this->defaultTransportClass = $this->_transport['class'];
		}
	}

	/**
	 * Gets the transport instance.
	 * @return array|TransportInterface
	 * @throws \yii\base\InvalidConfigException on invalid transport configuration.
	 */
	public function getTransport()
	{
		if (!is_object($this->_transport)) {
			if ($this->getIsMultipleTransport()) {
				if (!isset($this->defaultTransportClass) || !class_exists($this->defaultTransportClass)) {
					throw new InvalidConfigException('"' . get_class($this) . '::defaultTransportClass" must be a valid transport class name when multiple transport is configured.');
				}
				foreach ($this->_transport as	$transport) {
					$transportClass = is_array($transport) ? $transport['class'] : get_class($transport);
					if ($transportClass == $this->defaultTransportClass) {
						$this->_transport = $transport;
						break;
					}
				}
				if (is_object($this->_transport)) {
					return $this->_transport;
				}
			}
			$this->_transport = $this->createTransport($this->_transport);
		}
		return $this->_transport;
	}

	/**
	 * Sets the transport configuration.
	 * @param array|TransportInterface $transport
	 * @throws InvalidConfigException on invalid argument.
	 */
	public function setTransport($transport)
	{
		if (!is_array($transport) && !is_object($transport)) {
			throw new InvalidConfigException('"' . get_class($this) . '::transport" should be either object or array, "' . gettype($transport) . '" given.');
		}
		$this->_transport = $transport;
	}

	/**
	 * Adds a new transport to the list.
	 * @param array|TransportInterface $transport
	 * @throws InvalidConfigException on invalid argument.
	 */
	public function addTransport($transport)
	{
		if (!is_array($transport) && !is_object($transport)) {
			throw new InvalidConfigException('"' . get_class($this) . '::transport" should be either object or array, "' . gettype($transport) . '" given.');
		}
		if (!empty($this->_transport)) {
			// Check if a single transport configuration is used and convert it to multiple transport configuration
			if (!$this->getIsMultipleTransport()) {
				$this->_transport = [$this->_transport];
			}
			$this->_transport[] = $transport;
		} else {
			$this->setTransport($transport);
		}
	}

	/**
	 * Checks if the transport is configured as multiple.
	 * @return bool
	 */
	protected function getIsMultipleTransport()
	{
		return is_array($this->_transport) && !array_key_exists('class', $this->_transport);
	}

	/**
	 * Creates sms transport instance by its array configuration.
	 * @param array $config transport configuration.
	 * @throws \yii\base\InvalidConfigException on invalid transport configuration.
	 * @return TransportInterface transport instance.
	 */
	protected function createTransport(array $config)
	{
		if (!isset($config['class'])) {
			$config['class'] = $this->defaultTransportClass;
		}
		/* @var $transport TransportInterface */
		$transport = Yii::createObject($config);

		return $transport;
	}

	/**
	 * @inheritdoc
	 */
	public function sendMultiple(array $messages)
	{
		if (!$this->beforeSend($messages)) {
			return false;
		}
		$successCount = 0;

		Yii::info('Sending a batch of ' . count($messages) . ' sms messages', __METHOD__);

		if ($this->useFileTransport) {
			$successCount = $this->saveMessage($messages);
		} else {
			$successCount = $this->sendMessage($messages);
		}
		$this->afterSend($messages, $successCount);

		return $successCount;
	}

	/**
	 * @inheritdoc
	 */
	protected function sendMessage($message)
	{
		if (is_array($message)) {
			return $this->getTransport()->sendMultiple($message);
		}
		return $this->getTransport()->send($message);
	}
}
