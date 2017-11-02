<?php

namespace Homebase\Model\ORM;

use Homebase\Model\Entity as BasicEntity;
use Homebase\Model\LazyProperty;
use Homebase\Model\Property;
use Homebase\Model\ValueObject;

/**
 * @property-read int $id
 */
abstract class Entity extends BasicEntity
{
	protected $relatedEntitiesClassPropertyMap = array();

	private function __construct()
	{

	}

	/**
	 * Attach entity to state specified by $properties parameter
	 * @param array $properties array(string <property name> => mixed <property value>)
	 */
	public function attach($properties)
	{
		$this->originalValues = array();
		foreach ($properties as $propertyName => $propertyValue) {
			if (!$this->propertyExists($propertyName)) {
				trigger_error('Unable to attach property \''.$propertyName.'\' to entity \''.static::class.'\'. Property does not exist.', E_USER_ERROR);
				return;
			}

			$this->setPropertyValue($propertyName, $propertyValue);
		}
	}

	/**
	 * @param ValueObject $valueObject
	 * @return \static
	 */
	public static function createFromValueObject(ValueObject $valueObject)
	{
		$entity = new static();
		try {
			$properties = $entity->getPropertiesFromValueObject($valueObject);
		} catch (UnknownMappingException $exception) {
			trigger_error('Unable to create \''.static::class.'\' from \''.get_class($valueObject).'\'. Property parsing has failed.', E_USER_ERROR);
			return;
		}
		foreach ($properties as $name => $value) {
			$entity->setPropertyValue($name, $value);
		}
		return $entity;
	}

	/**
	 * @param array $properties
	 * @return \static
	 */
	public static function createNew(array $properties = array())
	{
		$entity = new static();
		$entity->setProperties($properties); // id can not be set
		return $entity;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function createProperty(array $propertyQualities)
	{
		$types = explode('|', $propertyQualities['type']);
		$validEntityType = $this->getValidEntityType($types);
		if ($validEntityType === null) {
			return parent::createProperty($propertyQualities);
		}

		$this->relatedEntitiesClassPropertyMap[$propertyQualities['name']] = $validEntityType;
		return LazyProperty::createProperty(
			$propertyQualities['name'],
			isset($propertyQualities['access']) ? $propertyQualities['access'] :null,
			isset($propertyQualities['type']) ? $propertyQualities['type'] :null,
			isset($propertyQualities['description']) ? $propertyQualities['description'] :null
		);

	}

	/**
	 * ValueObject <-> Entity mapping method
	 * Multiple implementation of ValueObjects are possible
	 * @param ValueObject $valueObject
	 * @return array array(string <property name> => mixed <property value>)
	 * @throws UnknownMappingException if mapping for Value Object is not known
	 */
	abstract protected function getPropertiesFromValueObject(ValueObject $valueObject);

	/**
	 * @return array array(string <property name> => string <entity class>)
	 */
	public final function getRelatedEntitiesClassPropertyMap()
	{
		return $this->relatedEntitiesClassPropertyMap;
	}

	/**
	 * @param string[] $types
	 * @return string|null
	 */
	private function getValidEntityType($types)
	{
		foreach ($types as $type) {
			$entityType = trim($type, '[]');
			if (is_a($entityType, Entity::class, true)) {
				return $type;
			}
		}

		return null;
	}

	/**
	 * @return boolean
	 */
	public function isChanged()
	{
		foreach ($this->getProperties() as $property) {
			if ($this->isPropertyChanged($property->getName())) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isNew()
	{
		return !isset($this->id);
	}

	/**
	 * Reset entity to last known attached state
	 * @param string[] $propertyNames when empty all properties are reset
	 */
	public function reset($propertyNames = array())
	{
		if ($this->isNew()) {
			trigger_error('Unable to reset entity \''.static::class.'\'. Entity is new and thus can not be reset. Use \''.static::class.'::isNew\' method to avoid this exception.', E_USER_WARNING);
			return;
		}

		$properties = $this->getProperties();
		foreach ($this->originalValues as $propertyName => $originalValue) {
			if (!in_array($propertyName, $propertyNames, TRUE)) {
				continue;
			}
			if (!isset($properties[$propertyName])) {
				trigger_error('Unable to reset property \''.$propertyName.'\ in \''.static::class.'\' entity. No such property exists. There was some error in setting of original value.');
			}
			/* @var $property Property */
			$property = $properties[$propertyName];
			if ($property instanceof LazyProperty) {
				$property->unsetValue(); // This allows to set new callback or execute the one already set again. New execution might get different result.
			}
			$this->$propertyName = $originalValue;
		}
	}

	/**
	 * @param array|ValueObject $properties
	 */
	public function setProperties($properties)
	{
		if ($properties instanceof ValueObject) {
			$properties = $this->getPropertiesFromValueObject($properties);
			if (isset($properties['id'])) { // Id is read-only
				unset($properties['id']);
			}
		}

		foreach ($properties as $name => $value) {
			$this->$name = $value;
		}
	}
}
