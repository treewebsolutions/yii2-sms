<?php

namespace tws\sms;

use Yii;

/**
 * Message extends the BaseMessage class.
 *
 * @see Sms
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class Message extends BaseMessage
{
	/**
	 * @var string the sender phone number.
	 */
	private $_from;

	/**
	 * @var string|array the message recipient(s).
	 */
	private $_to;

	/**
	 * @var string the message plain text content.
	 */
	private $_textBody;

	/**
	 * @var string the character set of this message.
	 */
	private $_charset;


	/**
	 * @inheritdoc
	 */
	public function getFrom()
	{
		return $this->_from;
	}

	/**
	 * @inheritdoc
	 */
	public function setFrom($from)
	{
		$this->_from = $from;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getTo()
	{
		return $this->_to;
	}

	/**
	 * @inheritdoc
	 */
	public function setTo($to)
	{
		$this->_to = $to;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function setTextBody($textBody)
	{
		$this->_textBody = $textBody;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getCharset()
	{
		return $this->_charset;
	}

	/**
	 * @inheritdoc
	 */
	public function setCharset($charset)
	{
		$this->_charset = $charset;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function toString()
	{
		return $this->_textBody;
	}
}
