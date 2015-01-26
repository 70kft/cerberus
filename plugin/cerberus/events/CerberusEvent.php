<?php
namespace Craft;

/**
 * Cerberus events
 */
class CerberusEvent extends Event
{
	/**
	 * @var bool Whether the submission is valid.
	 */
	public $isValid = true;

	/**
	 * @var bool Whether we should pretend the submission went through, but it really didn't.
	 */
	public $fakeIt = false;
}
