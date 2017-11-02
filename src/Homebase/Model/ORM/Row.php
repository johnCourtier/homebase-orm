<?php

namespace Homebase\Model\ORM;

use Homebase\Model\ValueObject;

abstract class Row extends ValueObject
{
	/**
	 * @param array $queryResult
	 * @return array array(string <property name> => mixed <property value>)
	 * @throws UnknownMappingException if mapping for Value Object is not known
	 */
	abstract protected function getPropertiesFromQueryResult(array $queryResult);

	/**
	 * Mapping should return only changed properties for already attached entity
	 * @param Entity $entity
	 * @return array array(string <property name> => mixed <property value>)
	 * @throws UnknownMappingException if mapping for Value Object is not known
	 */
	abstract protected function getPropertiesFromEntity(Entity $entity);

	/**
	 * @param Entity $entity
	 * @return static
	 */
	public static function createFromEntity(Entity $entity)
	{
		$row = new static;
		try {
			$properties = $row->getPropertiesFromEntity($entity);
		} catch (UnknownMappingException $exception) {
			trigger_error('Unable to create \''.static::class.'\' from \''.get_class($entity).'\'. Property parsing has failed.', E_USER_ERROR);
			return;
		}

		foreach ($properties as $name => $value) {
			$row->$name = $value;
		}

		return $row;
	}

	/**
	 * @param array $queryResult array(string <column name> => mixed <column value>)
	 * @return static
	 */
	public static function createFromQueryResult(array $queryResult)
	{
		$row = new static;
		try {
			$properties = $row->getPropertiesFromQueryResult($queryResult);
		} catch (UnknownMappingException $exception) {
			trigger_error('Unable to create \''.static::class.'\' from query result. Property parsing has failed.', E_USER_ERROR);
			return;
		}

		foreach ($properties as $name => $value) {
			$row->$name = $value;
		}

		return $row;
	}
}
