<?php
namespace Craft;

/**
 * Class CerberusModel
 *
 * @author		Greg Hayes <greg@70kft.com>
 * @package		Craft
 * @copyright	2014 70kft
 * @license		[MIT]
 */
class CerberusModel extends BaseModel
{
	public function defineAttributes()
	{
		return array(
			'id'			=> array(AttributeType::Number),
			'email'		=> array(AttributeType::Email),
			'author'		=> array(AttributeType::String,	'maxLength'	=> 50),
			'content'		=> array(AttributeType::String,	'column'	=> ColumnType::Text),
			'isKeyValid'		=> AttributeType::Bool,
			'flaggedAsSpam'	=> AttributeType::Bool,
			'isSpam'		=> AttributeType::Bool,
			'isHam'		=> AttributeType::Bool,
			'data'		=> AttributeType::Mixed,
			'dateCreated'	=> AttributeType::DateTime,
			'dateUpdated'	=> AttributeType::DateTime,


			'fromName'   		=> array(AttributeType::String, 'label' => 'Your Name'),
			'fromFirstName'   	=> array(AttributeType::String, 'label' => 'Your First Name'),
			'fromLastName'   	=> array(AttributeType::String, 'label' => 'Your Last Name'),
			'formId'   	 		=> array(AttributeType::String, 'label' => 'Form ID'),
			'fromEmail'  		=> array(AttributeType::Email,  'required' => true, 'label' => 'Your Email'),
			'message'    		=> array(AttributeType::String, 'required' => true, 'label' => 'Message'),
			'subject'    		=> array(AttributeType::String, 'label' => 'Subject'),
			'company'    		=> array(AttributeType::String, 'label' => 'Company'),
			'title'    			=> array(AttributeType::String, 'label' => 'Title'),
			'phone'    			=> array(AttributeType::String, 'label' => 'Phone'),
			'pageURL'			=> array(AttributeType::String, 'label' => 'Page URL'),
			'pageTitle'			=> array(AttributeType::String, 'label' => 'Page Title'),
			'token'			=> array(AttributeType::String, 'label' => 'token'),
			'attachment' 		=> AttributeType::Mixed,
		);
	}
}
