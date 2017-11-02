<?php

namespace Examples\Author\MySQL;

use Homebase\Model\ORM\IdentityRow;

/**
 * @property int $id
 * @property string $name
 * @property string $birthDate
 */
class Row extends IdentityRow
{
	protected function convertPropertyForEntity($propertyName, $originalValue)
	{
		if ($propertyName === 'birtDate') {
			return new \DateTime($originalValue);
		}

		return parent::convertPropertyForEntity($propertyName, $originalValue);
	}

	protected function convertPropertyForQuery($propertyName, $originalValue)
	{
		if ($propertyName === 'birtDate') {
			return $originalValue->format('Y-m-d H:i:s');
		}

		return parent::convertPropertyForQuery($propertyName, $originalValue);
	}
}
