<?php

namespace Examples\Author;

use DateTime;
use Examples\Author\MySQL\Row as AuthorMySQLRow;
use Homebase\Model\ORM\Entity as ORMEntity;
use Homebase\Model\ORM\UnknownMappingException;
use Homebase\Model\ValueObject;

/**
 * @property string $name
 * @property \Examples\Book\Entity[] $bookEntities
 * @property \DateTime $birthDate
 */
class Entity extends ORMEntity
{
	/**
	 * {@inheritDoc}
	 */
	protected function getPropertiesFromValueObject(ValueObject $valueObject)
	{
		if ($valueObject instanceOf AuthorMySQLRow) {
			return array(
				'id' => $valueObject->id,
				'name' => $valueObject->name,
				'birthDate' => new DateTime($valueObject->birthDate),
			);
		}

		throw new UnknownMappingException('Unable to get properties of \''.get_class($this).'\' entity from \''.get_class($valueObject).'\' value object. No mapping was defined.');
	}
}
