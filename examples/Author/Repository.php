<?php

namespace Examples\Author;

use Homebase\Model\ORM\Repository as ORMRepository;

class Repository extends ORMRepository
{
	/**
	 * {@inheritDoc}
	 * @return Entity[]
	 */
	public function findEntities($restrictions)
	{
		parent::findEntities($restrictions);
	}
}
