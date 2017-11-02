<?php

namespace Homebase\Model\ORM;

/**
 * @property Homebase\Model\ORM\Expression $id
 */
abstract class Restriction extends \Homebase\Model\ValueObject
{
	const JOIN_EXCLUSIVE = 'exclusive';
	const JOIN_INCLUSIVE = 'inclusive';

	/** @var Restriction[] array(string <static::JOIN_*> => Restriction[] <joined restrictions>) */
	private $restrictions = array();

	protected function __construct()
	{
		// protected access
	}

	/**
	 * @return \static
	 */
	public static function create()
	{
		return new static;
	}

	/**
	 * @return \Homebase\Model\ORM\Expression[]
	 */
	public function getExpressions()
	{
		$properties = $this->getProperties();
		$expressions = array();
		foreach ($properties as $property) {
			if ($property instanceof Expression) {
				$expressions[] = $property;
			}
		}

		return $expressions;
	}

	/**
	 * @param string $operation static::JOIN_*
	 * @return Restriction[]
	 */
	public function getRestrictions($operation)
	{
		if (isset($this->restrictions[$operation])) {
			return $this->restrictions[$operation];
		}

		return array();
	}

	/**
	 * @param \Homebase\Model\ORM\Restriciton $restriction
	 * @param string $operation
	 */
	public function joinRestriction(Restriction $restriction, $operation = self::JOIN_EXCLUSIVE)
	{
		$this->restrictions[$operation] = $restriction;
	}
}