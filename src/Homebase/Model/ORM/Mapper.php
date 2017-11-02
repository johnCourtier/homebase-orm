<?php

namespace Homebase\Model\ORM;

interface Mapper
{
	/**
	 * @param string $className class name of Repository|Restriction
	 * @return string class name of \Homebase\Model\ORM\Entity
	 */
	public function getEntityClass($className);

	/**
	 * @param string $className
	 * @return string class name of \Homebase\Model\ORM\Repository
	 */
	//public function getRepositoryClass($className);

	/**
	 * @param string $className class name of Repository|Entity
	 * @return string class name of \Homebase\Model\ORM\Restriction
	 */
	public function getRestrictionClass($className);

	/**
	 * @param string $className
	 * @return string
	 */
	//public function getForeignKey($className);

	/**
	 * @param string $className class name of Repository|Restriction|Entity
	 * @param string $connectionClassName
	 * @return string class name of \Homebase\Model\ORM\Row
	 */
	public function getRowClass($className, $connectionClassName);

	/**
	 * @param string $className class name of Repository|Restriction|Entity
	 * @return string
	 */
	public function getRelationColumnName($className);
}
