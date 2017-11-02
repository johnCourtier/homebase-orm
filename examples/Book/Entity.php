<?php

namespace Examples\Book;

use Examples\Book\MySQL\Row as BookMySQLRow;
use Homebase\Model\ORM\Entity as ORMEntity;
use Homebase\Model\ORM\UnknownMappingException;
use Homebase\Model\ValueObject;

/**
 * @property string $title
 * @property-read int $authorId
 * @property \Examples\Author\Entity $authorEntity
 */
class Entity extends ORMEntity
{
	/**
	 * {@inheritDoc}
	 */
	protected function getPropertiesFromValueObject(ValueObject $valueObject)
	{
		if ($valueObject instanceOf BookMySQLRow) {
			return array(
				'id' => $valueObject->id,
				'title' => $valueObject->title,
				'authorId' => $valueObject->authorId,
			);
		}

		throw new UnknownMappingException('Unable to get properties of \''.get_class($this).'\' entity from \''.get_class($valueObject).'\' value object. No mapping was defined.');
	}
}
