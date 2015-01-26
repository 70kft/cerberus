<?php
namespace Craft;

use Guzzle\Http\Client;

/**
 * Class CerberusService
 *
 * @author      Selvin Ortiz <selvin@selv.in>
 * @package     Craft
 * @copyright   2014 Selvin Ortiz
 * @license     [MIT]
 */
class CerberusService extends BaseApplicationComponent
{
    /**
     * The HTTP client to user for all requests
     * @var Client
     */
    protected $httpClient;

    /**
     * The plugin settings model
     * @var BaseModel
     */
    protected $pluginSettings;

    /**
     * The URL endpoint to the akismet API
     * @var string
     */
    protected $endpoint = 'rest.akismet.com/1.1';

    /**
     * Initializes this component, Guzzle client, and plugin settings
     */
    public function init()
    {
        $this->httpClient       = new Client;
        $this->pluginSettings   = craft()->plugins->getPlugin('cerberus')->getSettings();

        // Craft::import('plugins.cerberus.common.CerberusInvalidKeyException');

        parent::init();
    }

    /**
     * @param Client $httpClient
     */
    public function setHttpClient(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param BaseModel $pluginSettings
     */
    public function setPluginSettings(BaseModel $pluginSettings)
    {
        $this->pluginSettings = $pluginSettings;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->pluginSettings->getAttribute('akismetApiKey');
    }

    // /**
    //  * @return string
    //  */
    // public function getUUIDToken() {
    //  var_dump($this->pluginSettings->getAttribute('uuidToken'));
    //  return $this->pluginSettings->getAttribute('uuidToken');
    // }

    /**
     * Ensures that a valid origin URL is set and returns it
     *
     * @return string
     */
    public function getOriginUrl()
    {
        $originUrl = $this->pluginSettings->getAttribute('akismetOriginUrl');
        $originUrl = trim($originUrl);

        if (empty($originUrl) || '{siteUrl}' === $originUrl)
        {
            return craft()->getSiteUrl();
        }

        return $originUrl;
    }

    /**
     * Checks whether the API key is valid
     *
     * @return bool
     */
    public function isKeyValid()
    {
        $params = array(
            'key'   => $this->getApiKey(),
            'blog'  => $this->getOriginUrl(),
        );

        $request    = $this->httpClient->post($this->getKeyEndpoint(), null, $params);
        $response   = (string) $request->send()->getBody();

        return (bool) ('valid' === $response);
    }

    /**
     * Validates potential spam against the Akismet API
     *
     * @param array $data
     *
     * @throws CerberusInvalidKeyException
     * @return bool
     */
    public function detectSpam(array $data=array())
    {
        $params = $this->mergeWithDefaultParams(
            array(
                'comment_author'        => isset($data['author'])   ? $data['author']   : null,
                'comment_content'       => isset($data['content'])  ? $data['content']  : null,
                'comment_author_email'  => isset($data['email'])    ? $data['email']    : null,
            )
        );

        if ($this->isKeyValid())
        {
            $request    = $this->httpClient->post($this->getContentEndpoint(), null, $params);
            $response   = (string) $request->send()->getBody();

            return (bool) ('true' == $response);
        }

        throw new CerberusInvalidKeyException;
    }

    /**
     * @param array $data
     *
     * @throws CerberusInvalidKeyException
     * @return bool
     */
    public function submitSpam(array $data=array())
    {
        $params = $this->mergeWithDefaultParams(
            array(
                'comment_author'        => isset($data['author'])   ? $data['author']   : null,
                'comment_content'       => isset($data['content'])  ? $data['content']  : null,
                'comment_author_email'  => isset($data['email'])    ? $data['email']    : null,
            )
        );

        if ($this->isKeyValid())
        {
            $request    = $this->httpClient->post($this->getSpamEndpoint(), null, $params);
            $response   = (string) $request->send()->getBody();

            return (bool) ('Thanks for making the web a better place.' == $response);
        }

        throw new CerberusInvalidKeyException;
    }

    /**
     * @param array $data
     *
     * @throws CerberusInvalidKeyException
     * @return bool
     */
    public function submitHam(array $data=array())
    {
        $params = $this->mergeWithDefaultParams(
            array(
                'comment_author'        => isset($data['author'])   ? $data['author']   : null,
                'comment_content'       => isset($data['content'])  ? $data['content']  : null,
                'comment_author_email'  => isset($data['email'])    ? $data['email']    : null,
            )
        );

        if ($this->isKeyValid())
        {
            $request    = $this->httpClient->post($this->getHamEndpoint(), null, $params);
            $response   = (string) $request->send()->getBody();

            return (bool) ('Thanks for making the web a better place.' == $response);
        }

        throw new CerberusInvalidKeyException;
    }

    /**
     * @param array $params The parameters to be merged with defaults
     *
     * @return array
     */
    protected function mergeWithDefaultParams(array $params=array())
    {
        return array_merge(
            array(
                'blog'          => $this->getOriginUrl(),
                'user_ip'       => $this->getRequestingIp(),
                'user_agent'    => $this->getUserAgent(),
                'comment_type'  => 'Entry'
            ),
            $params
        );
    }

    /**
     * Ensures that we get the right IP address even if behind CloudFlare
     *
     * @return  string
     */
    public function getRequestingIp()
    {
        return isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Checks whether the content is considered spam as far as akismet is concerned
     *
     * @param array $data The array containing the key/value pairs to validate
     *
     * @example
     * $data        = array(
     *  'email'     => 'john@smith.com',
     *  'author'    => 'John Smith',
     *  'content'   => 'We are Smith & Co, one of the best companies in the world.'
     * )
     *
     * @note $data[content] is required
     *
     * @return bool
     */
    public function isSpam(array $data=array())
    {
        $isKeyValid     = true;
        $flaggedAsSpam  = false;

        try
        {
            $flaggedAsSpam = $this->detectSpam($data);
        }
        catch(CerberusInvalidKeyException $e)
        {
            if (craft()->userSession->isAdmin())
            {
                craft()->userSession->setError($e->getMessage());
                craft()->request->redirect(sprintf('/%s/settings/plugins/cerberus/', craft()->config->get('cpTrigger')));
            }
            else
            {
                $isKeyValid = false;

                Craft::log($e->getMessage(), LogLevel::Warning);
            }
        }

        $params = array_merge($data,
            array(
                'isKeyValid'    => $isKeyValid,
                'flaggedAsSpam' => $flaggedAsSpam
            )
        );

        $this->addLog($params);

        return (bool) $flaggedAsSpam;
    }

    /**
     * Contact Form beforeSend()
     *
     * Allows you to use Cerberus alongside the Contact Form plugin by P&T
     *
     * @since   0.4.7
     * @param   BaseModel $form
     * @return  boolean
     */
    public function detectContactFormSpam(BaseModel $form)
    {
        $data = array(
            'content'   => $form->getAttribute('message'),
            'author'    => $form->getAttribute('fromName'),
            'email' => $form->getAttribute('fromEmail'),
        );

        return $this->isSpam($data);
    }

    /**
     * Implements support for the Guest Entries
     *
     * @since   0.6.0
     *
     * @param   BaseModel $model
     * @return  bool
     */
    public function detectDynamicFormSpam(BaseModel $model)
    {
        $data           = array();

        $data['email']  = craft()->request->getPost('cerberus.emailField');
        $data['author'] = craft()->request->getPost('cerberus.authorField');
        $data['content']    = craft()->request->getPost('cerberus.contentField');

        $data           = $this->renderObjectFields($data, $model);

        if (false == $data)
        {
            CerberusPlugin::log('Unable to fetch the field values from the entry, possibly due to wrong field definition.', LogLevel::Error);

            return false;
        }

        return $this->isSpam($data);
    }

    /**
     * Deletes a log by id
     *
     * @param $id
     *
     * @return bool
     * @throws \CDbException
     */
    public function deleteLog($id)
    {
        $log = CerberusRecord::model()->findById($id);

        if ($log)
        {
            $log->delete();

            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function deleteLogs()
    {
        return CerberusRecord::model()->deleteAll();
    }

    /**
     * Returns an array of logs if any are found
     *
     * @param array $attributes
     *
     * @return array
     */
    public function getLogs(array $attributes=array())
    {
        $models     = array();
        $records    = CerberusRecord::model()->findAllByAttributes($attributes);

        if ($records)
        {
            foreach ($records as $record)
            {
                $models[] = CerberusModel::populateModel($record->getAttributes());
            }
        }

        return $models;
    }

    /**
     * Creates a new submission log if logging is enabled
     *
     * @param $data
     *
     * @return bool
     */
    public function addLog($data)
    {
        if ($this->pluginSettings->getAttribute('logSubmissions'))
        {
            $record = new CerberusRecord;

            $record->setAttributes($data, false);

            if ($record->validate())
            {
                $record->save();
            }
        }

        return false;
    }

    /**
     * Returns an array of variables to be used by the index or settings templates
     *
     * @param bool $includeLogs
     * @return array
     */
    public function getTemplateVariables($includeLogs=false)
    {
        $plugin     = craft()->plugins->getPlugin('cerberus');
        $settings   = $this->pluginSettings->getAttributes();
        $variables  = array();

        $variables['name']          = $plugin->getName(true);
        $variables['alias']         = $plugin->getName();
        $variables['version']       = $plugin->getVersion();
        $variables['developer']     = $plugin->getDeveloper();
        $variables['developerUrl']  = $plugin->getDeveloperUrl();
        $variables['settings']      = $settings;

        if ($includeLogs)
        {
            $variables['logs']      = $this->getLogs();
        }

        return $variables;
    }

    /**
    * Sends an email through HubSpot submitted through a contact form.
    *
    * @param CerberusModel $message
    * @throws Exception
    * @return bool
    */
    public function onSendMessage(CerberusModel $message) {
        $settings = craft()->plugins->getPlugin('cerberus')->getSettings();

        // Fire an 'onBeforeSend' event
        Craft::import('plugins.contactform.events.ContactFormEvent');
        $event = new ContactFormEvent($this, array('message' => $message));
        $this->onBeforeSend($event);

        if ($event->isValid)
        {
            if (!$event->fakeIt)
            {
                $email = new EmailModel();
                $emailSettings = craft()->email->getSettings();

                $email->fromEmail = $emailSettings['emailAddress'];
                $email->replyTo   = $message->fromEmail;
                $email->sender    = $emailSettings['emailAddress'];
                $email->fromName  = $settings->prependSender . ($settings->prependSender && $message->fromName ? ' ' : '') . $message->fromName;
                $email->toEmail   = $message->toEmail . '@partners-cap.com';
                $email->subject   = $settings->prependSubject . ($settings->prependSubject && $message->subject ? ' - ' : '') . $message->subject;
                $email->body      = $message->message;

                if ($message->attachment)
                {
                    $email->addAttachment($message->attachment->getTempName(), $message->attachment->getName(), 'base64', $message->attachment->getType());
                }

                craft()->email->sendEmail($email);
            }

            return true;
        }

        return false;
    }

    /**
     * Parses fields with twig support used in mappings
     *
     * @param array $fields
     * @param BaseModel $object
     *
     * @return array|false
     */
    protected function renderObjectFields(array $fields, BaseModel $object)
    {
        try
        {
            foreach ($fields as $field => $value)
            {
                $fields[$field] = craft()->templates->renderObjectTemplate($value, $object);
            }
        }
        catch (\Exception $e)
        {
            CerberusPlugin::log($e->getMessage(), LogLevel::Error);

            return false;
        }

        return $fields;
    }

    protected function getUserAgent()
    {
        return 'Craft 2.2 | Cerberus 0.1';
    }

    protected function getKeyEndpoint()
    {
        return sprintf('http://%s/verify-key', $this->endpoint);
    }

    protected function getContentEndpoint()
    {
        return sprintf('http://%s.%s/comment-check', $this->getApiKey(), $this->endpoint);
    }

    protected function getSpamEndpoint()
    {
        return sprintf('http://%s.%s/submit-spam', $this->getApiKey(), $this->endpoint);
    }

    protected function getHamEndpoint()
    {
        return sprintf('http://%s.%s/submit-ham', $this->getApiKey(), $this->endpoint);
    }
    /**
     * Fires an 'onBeforeSend' event.
     *
     * @param ContactFormEvent $event
     */
    public function onBeforeSend(ContactFormEvent $event)
    {
        $this->raiseEvent('onBeforeSend', $event);
    }
}
