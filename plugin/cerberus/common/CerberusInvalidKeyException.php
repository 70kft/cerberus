<?php
namespace Craft;

/**
 * Class CerberusInvalidKeyException
 *
 * @author		Greg Hayes <greg@70kft.com>
 * @package		Craft
 * @copyright	2014 70kft
 * @license		[MIT]
 */
class CerberusInvalidKeyException extends Exception
{
	/**
	 * @inheritdoc
	 */
	public function __construct($message=null, $code=0, $previous=null)
	{
		if (null === $message)
		{
			$message = Craft::t('Your API Key is not valid or has expired, please fix that.');
		}

		parent::__construct($message, $code, $previous);
	}
}
