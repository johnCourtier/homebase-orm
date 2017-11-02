<?php

namespace Homebase\Model\ORM;

interface Storage
{
	/**
	 * @param Restriction[] $restrictions
	 * @return Entity[]
	 */
	public function findEntities($restrictions);

	/**
	 * @param Entity[] $entities
	 * @return integer
	 */
	public function persistEntities(&$entities);

	/**
	 * @param Restriction[] $restrictions
	 * @return integer
	 */
	public function deleteEntities($restrictions);
}
