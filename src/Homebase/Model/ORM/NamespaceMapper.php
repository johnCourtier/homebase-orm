<?php

namespace Homebase\Model\ORM;

class NamespaceMapper implements Mapper
{
	/** @var array */
	protected $namespaceTableNamesMap = array();

	public function __construct($namespaceTableNamesMap)
	{
		$this->namespaceTableNamesMap = $namespaceTableNamesMap;
	}

	/**
	 * @param string $className
	 * @throws UnknownMappingException if class has no namespace
	 */
	protected function getNamespace($className)
	{
		$slashPosition = strrpos($className, '\\');
		if ($slashPosition === FALSE) {
			throw new UnknownMappingException('Unable to get namespace of \''.$className.'\'. Class has no namespace.');
		}

		return substr($className, 0, $slashPosition);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEntityClass($className)
	{
		try {
			$namespace = $this->getNamespace($className);
		} catch (\Homebase\Model\ORM\UnknownMappingException $exception) {
			trigger_error('Unable to get entity class. Class \''.$className.'\' has no namespace.', E_USER_ERROR);
			return '';
		}

		$entityClass = $namespace.'\\Entity';

		if (!is_a($entityClass, Entity::class, TRUE)) {
			trigger_error('Unable to get entity class. Mapped class \''.$entityClass.'\' is not an instance of \''.Entity::class.'\'.', E_USER_ERROR);
		}

		return $entityClass;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRelationColumnName($className)
	{
		try {
			$namespace = $this->getNamespace($className);
		} catch (\Homebase\Model\ORM\UnknownMappingException $exception) {
			trigger_error('Unable to get relation column name. Class \''.$className.'\' has no namespace.', E_USER_ERROR);
			return '';
		}

		if (!isset($this->namespaceTableNamesMap[$namespace])) {
			trigger_error('Unable to get relation column name. Namespace \''.$namespace.'\' has no table name mapped. Please add \''.$namespace.'\' key namespace table map.', E_USER_ERROR);
			return '';
		}

		return $this->namespaceTableNamesMap[$namespace].'Id';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRestrictionClass($className)
	{
		try {
			$namespace = $this->getNamespace($className);
		} catch (\Homebase\Model\ORM\UnknownMappingException $exception) {
			trigger_error('Unable to get restriction class. Class \''.$className.'\' has no namespace.', E_USER_ERROR);
			return '';
		}

		$restrictionClass = $namespace.'\\Restriction';

		if (!is_a($restrictionClass, Restriction::class, TRUE)) {
			trigger_error('Unable to get restriction class. Mapped class \''.$restrictionClass.'\' is not an instance of \''.Restriction::class.'\'.', E_USER_ERROR);
		}

		return $restrictionClass;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRowClass($className, $connectionClassName)
	{
		try {
			$namespace = $this->getNamespace($className);
		} catch (\Homebase\Model\ORM\UnknownMappingException $exception) {
			trigger_error('Unable to get row class. Class \''.$className.'\' has no namespace.', E_USER_ERROR);
			return '';
		}

		try {
			$connectionClassNamespace = $this->getNamespace($connectionClassName);
		} catch (\Homebase\Model\ORM\UnknownMappingException $exception) {
			trigger_error('Unable to get row class. Class \''.$connectionClassName.'\' has no namespace.', E_USER_ERROR);
			return '';
		}

		$slashPosition = strrpos($connectionClassNamespace, '\\');
		if ($slashPosition === FALSE) {
			$rowClass = $namespace.'\\'.$connectionClassNamespace.'\\Row';
		} else {
			$rowClass = $namespace.'\\'.substr($connectionClassNamespace, $slashPosition+1).'\\Row';
		}

		if (!is_a($rowClass, Row::class, TRUE)) {
			trigger_error('Unable to get row class. Mapped class \''.$rowClass.'\' is not an instance of \''.Row::class.'\'.', E_USER_ERROR);
		}

		return $rowClass;
	}
}
