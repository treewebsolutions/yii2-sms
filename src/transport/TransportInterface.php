<?php

namespace tws\sms\transport;

use tws\sms\MessageInterface;

/**
 * TransportInterface is the interface that should be implemented by SMS transport classes.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
interface TransportInterface
{
	/**
	 * Normalizes the message by mapping the class properties to the transport class needs.
	 * @param MessageInterface $message
	 */
	public function normalize($message);

	/**
	 * Sends the sms message using a specific transport logic.
	 * @param MessageInterface $message
	 */
	public function send($message);

	/**
	 * Sends multiple sms messages using a specific transport logic.
	 * @param MessageInterface[] $messages
	 */
	public function sendMultiple($messages);
}
