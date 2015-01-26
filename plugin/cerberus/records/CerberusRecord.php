<?php
namespace Craft;

/**
 * Class CerberusRecord
 *
 * @author		Greg Hayes <greg@70kft.com>
 * @package		Craft
 * @copyright	2014 70kft
 * @license		[MIT]
 */
class CerberusRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'cerberus';
	}

	public function defineAttributes()
	{
		return array(
			'email'		=> array(AttributeType::Email,	'required'	=> true),
			'author'		=> array(AttributeType::String,	'maxLength'	=> 50),
			'content'		=> array(AttributeType::String,	'column'	=> ColumnType::Text),
			'isKeyValid'		=> AttributeType::Bool,
			'flaggedAsSpam'	=> AttributeType::Bool,
			'isSpam'		=> AttributeType::Bool,
			'isHam'		=> AttributeType::Bool,
			'data'		=> AttributeType::Mixed
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('email'))
		);
	}
}
