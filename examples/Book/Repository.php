<?php

namespace Examples\Book;

use Homebase\Model\ORM\Repository as ORMRepository;

class Repository extends ORMRepository
{
	/**
	 * {@inheritDoc}
	 * @return Entity[]
	 */
	public function findEntities($restrictions)
	{
		return parent::findEntities($restrictions);
	}
}
