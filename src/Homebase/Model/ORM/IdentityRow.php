<?php

namespace Homebase\Model\ORM;

abstract class IdentityRow extends Row
{
	/**
	 * @param string $propertyName
	 * @param string|int|bool|null $originalValue
	 * @return mixed
	 */
	protected function convertPropertyForEntity($propertyName, $originalValue)
	{
		return $originalValue;
	}

	/**
	 * @param string $propertyName
	 * @param mixed $originalValue
	 * @return string|int|bool|null
	 */
	protected function convertPropertyForQuery($propertyName, $originalValue)
	{
		return $originalValue;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getPropertiesFromEntity(Entity $entity)
	{
		$properties = $this->getProperties();
		foreach ($properties as $property) {
			$propertyName = $property->getName();
			if (!$entity->propertyExists($propertyName)) {
				throw new UnknownMappingException('Unable to get value for \''.$propertyName.'\' property of \''.get_class($this).'\' class. No property \''.$propertyName.'\' exists in \''.get_class($entity).'\' entity.');
			}

			if (!$entity->isPropertyReadable($propertyName)) {
				throw new UnknownMappingException('Unable to get value for \''.$propertyName.'\' property of \''.get_class($this).'\' class. Property \''.$propertyName.'\' in \''.get_class($entity).'\' entity is not readable.');
			}

			$properties[$propertyName] = $this->convertPropertyForQuery($propertyName, $entity->$propertyName);
		}

		return $properties;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getPropertiesFromQueryResult(array $queryResult)
	{
		$properties = $this->getProperties();
		foreach ($properties as $property) {
			$propertyName = $property->getName();
			if (!isset($queryResult[$propertyName])) {
				throw new UnknownMappingException('Unable to get value for \''.$propertyName.'\' property of \''.get_class($this).'\' class. There is no \''.$propertyName.'\' key in query result.');
			}

			$properties[$propertyName] = $this->convertPropertyForEntity($propertyName, $queryResult[$propertyName]);
		}

		return $properties;
	}
}
