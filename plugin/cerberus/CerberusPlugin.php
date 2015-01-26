<?php
namespace Craft;

/**
 * Cerberus @v0.1
 *
 * Cerberus uses Akismet and a Server Side UUID invisible captcha to combat spam.
 *
 * Akismet integration based off of Selvin Ortiz's Spam Guard
 *
 * @author		Greg Hayes <greg@70kft.com>
 * @version		0.1
 * @package		Craft
 * @copyright	2014 70kft
 * @license		http://opensource.org/licenses/MIT
 * @copyright	2014 70kft
 */
class CerberusPlugin extends BasePlugin
{
	/**
	 * Enables support for third party plugins
	 *
	 * @throws Exception
	 * @return void
	 */
	public function init()
	{
		#
		# Support for Contact Forms
		if ($this->getSettings()->getAttribute('enableContactFormSupport'))
		{
			craft()->on('cerberus.sendMessage', function(Event $event)
			{
				// If the akismetApiKey is not provided in the settings we are going to skip the Akismet integration completely
				if ($settings->akismetApiKey !== "") {
					$spam = cerberus()->detectContactFormSpam($event->params['message']);
				}
				else
				{
					$spam = false;
				}

				$message = $event->params['message'];
				$token = $this->pluginSettings->getAttribute('uuidToken');

				if ($spam)
				{
					$event->fakeIt = true;
				}
				else if ($message->token != $token)
				{
					$event->fakeIt = true;
				}
			});
		}
	}

	/**
	 * Returns the name of the plugin or the alias given by the end user
	 *
	 * @param bool $real
	 *
	 * @return string
	 */
	public function getName($real=false)
	{
		$alias	= $this->getSettings()->getAttribute('pluginAlias');

		return ($real || empty($alias)) ? 'Cerberus' : $alias;
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return '0.1';
	}

	/**
	 * @return string
	 */
	public function getDeveloper()
	{
		return '70kft';
	}

	/**
	 * @return string
	 */
	public function getDeveloperUrl()
	{
		return 'http://70kft.com';
	}

	/**
	 * @return bool
	 */
	public function hasCpSection()
	{
		return $this->getSettings()->getAttribute('enableCpTab');
	}

	/**
	 * @return array
	 */
	public function registerCpRoutes()
	{
		return array(
			'cerberus'	=> array('action' => 'cerberus/index')
		);
	}

	/**
	 * @return array
	 */
	public function defineSettings()
	{
		return array(
			'akismetApiKey'				=> array(AttributeType::String,	'required'	=> false,	'maxLength' => 25),
			'akismetOriginUrl'			=> array(AttributeType::String,	'required'	=> true),
			'uuidToken'					=> array(AttributeType::String,	'required'	=> true,	'maxLength' => 40),
			'enableContactFormSupport'	=> array(AttributeType::Bool,	'default'	=> true),
			'logSubmissions'			=> array(AttributeType::Bool,	'default'	=> false),
			'enableCpTab'				=> array(AttributeType::Bool,	'default'	=> true),
			'pluginAlias'				=> AttributeType::String,
			'baseURL'					=> array(AttributeType::String, 'required' => true),
		);
	}

	/**
	 * @return string
	 */
	public function getSettingsHtml()
	{
		craft()->templates->includeCssResource('cerberus/css/cerberus.css');

		return craft()->templates->render('cerberus/_settings', cerberus()->getTemplateVariables());
	}
}


/**
 * Enables service layer encapsulation and proper hinting
 *
 * @return CerberusService
 */
function cerberus()
{
	return craft()->cerberus;
}
