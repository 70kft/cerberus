<?php
namespace Craft;

/**
 * Class CerberusController
 *
 * @author		Greg Hayes <greg@70kft.com>
 * @package		Craft
 * @copyright	2014 70kft
 * @license		[MIT]
 */
class CerberusController extends BaseController
{
	/**
	 * Renders the index template to improve performance
	 *
	 * @return string
	 */
	public function actionIndex()
	{
		craft()->templates->includeCssResource('cerberus/css/cerberus.css');

		$this->renderTemplate('cerberus/_index', cerberus()->getTemplateVariables(true));
	}

	/**
	 * Removes a log from the db if it exists and the user has the proper permissions
	 *
	 * @throws HttpException
	 */
	public function actionDeleteLog()
	{
		$this->requireAdmin();
		$this->requirePostRequest();

		$id			= craft()->request->getPost('id');
		$deleted	= false;

		if ($id)
		{
			$deleted = cerberus()->deleteLog($id);
		}

		if ($deleted)
		{
			craft()->userSession->setNotice(Craft::t('Log deleted successfully.'));
		}
		else
		{
			craft()->userSession->setError(Craft::t('Unable to delete the log.'));
		}

		$this->redirectToPostedUrl();
	}

	/**
	 * Removes all logs from the db if any exist and the user has the proper permissions
	 *
	 * @throws HttpException
	 */
	public function actionDeleteLogs()
	{
		$this->requireAdmin();
		$this->requirePostRequest();

		$confirmation	= craft()->request->getPost('confirmation', false);

		if ($confirmation)
		{
			$deleted = cerberus()->deleteLogs();

			if ($deleted)
			{
				craft()->userSession->setNotice(Craft::t('All logs were deleted successfully.'));
			}
			else
			{
				craft()->userSession->setError(Craft::t('Unable to delete all logs.'));
			}
		}
		else
		{
			craft()->userSession->setError(Craft::t('Please check the box to confirm this action.'));
		}

		$this->redirectToPostedUrl();
	}

	public function actionSendMessage()
	{
		$this->requirePostRequest();

		$settings = craft()->plugins->getPlugin('cerberus')->getSettings();

		$message = new CerberusModel();
		$savedBody = false;

		$message->fromEmail  	= craft()->request->getPost('fromEmail');
		$message->fromFirstName	= craft()->request->getPost('fromFirstName');
		$message->fromLastName	= craft()->request->getPost('fromLastName');
		$message->formId	 	= craft()->request->getPost('formId');
		$message->fromName	 	= craft()->request->getPost('fromName');
		$message->subject    	= craft()->request->getPost('subject');
		$message->company    	= craft()->request->getPost('company');
		$message->title    		= craft()->request->getPost('title');
		$message->phone    		= craft()->request->getPost('phone');
		$message->pageURL    	= craft()->request->getPost('pageURL');
		$message->pageTitle    	= craft()->request->getPost('pageTitle');
		$message->token    		= craft()->request->getPost('token');
		$message->attachment 	= \CUploadedFile::getInstanceByName('attachment');

		// Set the message body
		$postedMessage = craft()->request->getPost('message');

		if ($postedMessage)
		{
			if (is_array($postedMessage))
			{
				$savedBody = false;

				if (isset($postedMessage['body']))
				{
					// Save the message body in case we need to reassign it in the event there's a validation error
					$savedBody = $postedMessage['body'];
				}

				// If it's false, then there was no messages[body] input submitted.  If it's '', then validation needs to fail.
				if ($savedBody === false || $savedBody !== '')
				{
					// Compile the message from each of the individual values
					$compiledMessage = '';

					foreach ($postedMessage as $key => $value)
					{
						if ($key != 'body')
						{
							if ($compiledMessage)
							{
								$compiledMessage .= "\n\n";
							}

							$compiledMessage .= $key.': ';

							if (is_array($value))
							{
								$compiledMessage .= implode(', ', $value);
							}
							else
							{
								$compiledMessage .= $value;
							}
						}
					}

					if (!empty($postedMessage['body']))
					{
						if ($compiledMessage)
						{
							$compiledMessage .= "\n\n";
						}

						$compiledMessage .= $postedMessage['body'];
					}

					$message->message = $compiledMessage;
				}
			}
			else
			{
				$message->message = $postedMessage;
			}
		}

		if ($message->validate())
		{
			// Only actually send it if the honeypot test was valid
			if($settings->uuidToken === $message->token)
			{

				// If the akismetApiKey is not provided in the settings we are going to skip the Akismet integration completely
				if ($settings->akismetApiKey !== "") {
					$spam = cerberus()->detectContactFormSpam($message);
				}
				else
				{
					$spam = false;
				}

				Craft::import('plugins.cerberus.events.CerberusEvent');
				$event = new CerberusEvent($this, array('message' => $message));

				if ($spam)
				{
					$event->fakeIt = true;
				}
				else
				{
					$event->fakeIt = true;
					craft()->cerberus->onSendMessage($message);
				}

				if (craft()->request->isAjaxRequest())
				{
					$this->returnJson(array('success' => true));
				}
				else
				{
					// Deprecated. Use 'redirect' instead.
					$successRedirectUrl = craft()->request->getPost('successRedirectUrl');

					if ($successRedirectUrl)
					{
						$_POST['redirect'] = $successRedirectUrl;
					}

					craft()->userSession->setNotice('Your message has been sent.');
					$this->redirectToPostedUrl($message);
				}
			}
			else {
				$event->fakeIt = true;
			}
		}

		// Something has gone horribly wrong.
		if (craft()->request->isAjaxRequest())
		{
			return $this->returnErrorJson($message->getErrors());
		}
		else
		{
			craft()->userSession->setError('There was a problem with your submission, please check the form and try again!');

			if ($savedBody !== false)
			{
				$message->message = $savedBody;
			}

			craft()->urlManager->setRouteVariables(array(
				'message' => $message
			));
		}
	}

	/**
	 * Returns the UUID in the plugin settings
	 *
	 */

	protected $allowAnonymous = true;
	protected $pluginSettings;

	public function actionGetUUIDToken() {
		$this->pluginSettings	= craft()->plugins->getPlugin('cerberus')->getSettings();
		echo($this->pluginSettings->getAttribute('uuidToken'));
		exit();
	}
}
