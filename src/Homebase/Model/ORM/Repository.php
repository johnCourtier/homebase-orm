<?php

namespace Homebase\Model\ORM;

use Homebase\Model\Callback;

abstract class Repository implements Storage
{
	/** @var Connection */
	protected $connection;

	/** @var Mapper */
	protected $mapper;

	/** @var QueryBuilder */
	protected $queryBuilder;

	public function __construct(
		Connection $connection,
		Mapper $mapper,
		QueryBuilder $queryBuilder
	) {
		$this->connection = $connection;
		$this->mapper = $mapper;
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @param Restriction[] $restrictions
	 * @return integer
	 */
	public function deleteEntities($restrictions)
	{
		$query = $this->queryBuilder->getDeleteQuery($restrictions);
		try {
			$rowCount = $this->connection->execute($query);
		} catch (BadQueryException $exception) {
			throw new BadQueryException('Unable to delete entities. Bad query \''.$query.'\' has been executed.', 500, $exception);
		} catch (DatabaseManagementSystemException $exception) {
			throw new DatabaseManagementSystemException('Unable to delete entities. Database management system failure has been encountered.', 500, $exception);
		}
		$this->connection->clearCachedQueryResults();

		return $rowCount;
	}

	/**
	 * @param Restriction[] $restrictions
	 * @return Row[]
	 * @throws BadQueryException if bad query was generated
	 * @throws DatabaseManagementSystemException if database management system fails
	 */
	private function getQueryResults($restrictions)
	{
		$query = $this->queryBuilder->getRetrieveQuery($restrictions);

		try {
			$queryResults = $this->connection->execute($query);
		} catch (BadQueryException $exception) {
			throw new BadQueryException('Unable to get rows. Bad query \''.$query.'\' has been executed.', 500, $exception);
		} catch (DatabaseManagementSystemException $exception) {
			throw new DatabaseManagementSystemException('Unable to get rows. Database management system failure has been encountered.', 500, $exception);
		}

		return $queryResults;
	}

	/**
	 * @param Restriction[] $restrictions
	 * @return Entity[] key are entities id
	 * @throws BadQueryException if bad query was generated
	 * @throws DatabaseManagementSystemException if database management system fails
	 */
	public function findEntities($restrictions)
	{
		$queryResults = $this->getQueryResults($restrictions);
		if (empty($queryResults)) {
			return array();
		}
		$rowClass = $this->mapper->getRowClass(get_class($this), get_class($this->connection));
		if (!is_a($rowClass, Row::class, true)) {
			trigger_error('Unable to create row of \''.static::class.'\' repository. Mapper returns \''.$rowClass.'\' which is not instance of \''.Row::class.'\'.', E_USER_ERROR);
			return array();
		}
		$rows = array();
		foreach ($queryResults as $queryResult) {
			try {
				$rows[] = call_user_func_array(array($rowClass, 'createFromQueryResult'), array($queryResult));
			} catch (\Homebase\Model\ORM\UnknownMappingException $exception) {
				trigger_error($exception->getMessage(), E_USER_ERROR);
			}
		}
		return $this->createEntities($rows);
	}

	/**
	 * Creates Entity with type based on $row type
	 * @param Row $row
	 * @param string $entityClass
	 * @return Entity
	 */
	private function createEntity(Row $row, $entityClass)
	{
		return call_user_func_array(array($entityClass, 'createFromValueObject'), array($row)); // Row is actually value object too
	}

	/**
	 * @param string $restrictionClass
	 * @return Restriction
	 */
	private function createRestriction($restrictionClass)
	{
		if (!is_a($restrictionClass, Restriction::class, true)) {
			trigger_error('Unable to create restriction of \''.static::class.'\' repository. Mapper returns \''.$restrictionClass.'\' which is not instance of \''.Restriction::class.'\'.', E_USER_ERROR);
		}
		return call_user_func(array($restrictionClass, 'create'));
	}

	/**
	 * @param Row[] $rows
	 * @param string|null $entityClass
	 * @return Entity[]
	 */
	private function createEntities($rows, $entityClass = null)
	{
		if (empty($rows)) {
			return array();
		}
		$entities = array();
		$ids = array();
		if ($entityClass === NULL) {
			$entityClass = $this->mapper->getEntityClass(static::class);
		}
		if (!is_a($entityClass, Entity::class, true)) {
			trigger_error('Unable to create entity of \''.static::class.'\' repository. Mapper returns \''.$entityClass.'\' which is not instance of \''.Entity::class.'\'.', E_USER_ERROR);
			return array();
		}
		foreach ($rows as $row) {
			$entity = $this->createEntity($row, $entityClass);
			if (!isset($entity->id)) {
				trigger_error('Creation of \''.get_class($entity).'\' has failed. No \'id\' was set. Please, check out out the mapping in \''.get_class($row).'::createFromQueryResult\' method.', E_USER_ERROR);
				return array();
			}
			$ids[] = $entity->id;
			$entities[$entity->id] = $entity;
		}

		$restrictionClass = $this->mapper->getRestrictionClass($entityClass);
		$restriction = $this->createRestriction($restrictionClass);
		$restriction->id = Expression::create(Expression::OPERATOR_IS_IN, $ids);

		foreach ($entities as $entity) {
			$properties = $this->getPropertiesForAttaching($entity, $restriction);
			$entity->attach($properties);
		}

		return $entities;
	}

	/**
	 * @param Restriction $restriction
	 * @param Entity $entity parent entity
	 * @throws BadQueryException if bad query was generated
	 * @throws DatabaseManagementSystemException if database management system fails
	 */
	private function getRelatedEntities(Restriction $restriction, Entity $entity)
	{
		$queryResults = $this->getQueryResults(array($restriction));
		if (empty($queryResults)) {
			return array();
		}
		$relationColumnName = $this->mapper->getRelationColumnName(get_class($entity));
		$rowClass = $this->mapper->getRowClass(get_class($restriction), get_class($this->connection));
		if (!is_a($rowClass, Row::class, true)) {
			trigger_error('Unable to create row of \''.get_class($restriction).'\' restriction. Mapper returns \''.$rowClass.'\' which is not instance of \''.Row::class.'\'.', E_USER_ERROR);
			return array();
		}
		$entityClass = $this->mapper->getEntityClass(get_class($restriction));
		if (!is_a($entityClass, Entity::class, true)) {
			trigger_error('Unable to create entity of \''.get_class($restriction).'\' restriction. Mapper returns \''.$entityClass.'\' which is not instance of \''.Entity::class.'\'.', E_USER_ERROR);
			return array();
		}
		$relatedRows = array();
		foreach ($queryResults as $queryResult) {
			if (!isset($queryResult[$relationColumnName])) {
				$relatedEntityClass = $this->mapper->getEntityClass(get_class($restriction));
				throw new BadQueryException('Unable to find related entities \''.$relatedEntityClass.'\' for \''.get_class($entity).'\'. Column \''.$relationColumnName.'\' is not set in retrieved rows. Please, make sure such column is retrieved to bind results correctly.');
			}

			if ($queryResult[$relationColumnName] != $entity->id) {
				continue; // Not related
			}

			try {
				$row = call_user_func_array(array($rowClass, 'createFromQueryResult'), array($queryResult));
			} catch (\Homebase\Model\ORM\UnknownMappingException $exception) {
				trigger_error($exception->getMessage(), E_USER_ERROR);
				return array();
			}
			$relatedRows[] = $row;
		}

		return $this->createEntities($relatedRows, $entityClass);
	}

	/**
	 * @param Entity $entity
	 * @param Restriction $restriction
	 * @param int|null $id id for attaching
	 * @return array array(string <property name> => Callback <related entities getter>)
	 */
	private function getPropertiesForAttaching(Entity $entity, Restriction $restriction)
	{
		$properties = array();

		$relatedEntitiesClassPropertyMap = $entity->getRelatedEntitiesClassPropertyMap();
		foreach ($relatedEntitiesClassPropertyMap as $propertyName => $relatedEntityClass) {
			$isEntityArrayAccepted = strpos($relatedEntityClass, '[]') !== FALSE;
			if ($isEntityArrayAccepted) {
				$relatedEntityClass = trim($relatedEntityClass, '[]');
			}
			$restrictionClass = $this->mapper->getRestrictionClass($relatedEntityClass);
			if (!is_a($restrictionClass, Restriction::class, true)) {
				trigger_error('Unable to create restriction of \''.$relatedEntityClass.'\' entity. Mapper returns \''.$restrictionClass.'\' which is not instance of \''.Restriction::class.'\'.', E_USER_ERROR);
				continue;
			}
			/* @var $relatedRestriction Restriction */
			$relatedRestriction = call_user_func(array($restrictionClass, 'create'));
			$relatedRestriction->joinRestriction($restriction, Restriction::JOIN_EXCLUSIVE);

			$properties[$propertyName] = new Callback(function(Restriction $restriction, Entity $entity) use($isEntityArrayAccepted, $relatedEntityClass, $propertyName) {
				$relatedEntities = $this->getRelatedEntities($restriction, $entity);
				if ($isEntityArrayAccepted) {
					return $relatedEntities;
				}

				if (empty($relatedEntities)) {
					return NULL;
				}

				if (count($relatedEntities) > 1) {
					trigger_error('Single \''.$relatedEntityClass.'\' entity was expected for \''.$propertyName.'\' property in \''.get_class($entity).'\' entity, but multiple results were obtained from DB.', E_USER_WARNING);
				}

				return reset($relatedEntities);
			}, array($relatedRestriction, $entity));
		}

		return $properties;
	}

	/**
	 * @param Entity[] $entities of same table
	 * @return int[] inserted ids
	 * @throws BadQueryException if bad query was generated
	 * @throws DatabaseManagementSystemException if database management system fails
	 */
	private function insertEntities(&$entities)
	{
		$rowClass = $this->mapper->getRowClass(get_class($this), get_class($this->connection));
		if (!is_a($rowClass, Row::class, true)) {
			trigger_error('Unable to create row of \''.static::class.'\' repository. Mapper returns \''.$rowClass.'\' which is not instance of \''.Row::class.'\'.', E_USER_ERROR);
			return array();
		}
		$rows  = array();
		foreach ($entities as $entity) {
			try {
				$rows[] = call_user_func_array(array($rowClass, 'createFromEntity'), array($entity));
			} catch (UnknownMappingException $exception) {
				trigger_error($exception->getMessage(), E_USER_ERROR);
				return array();
			}
		}
		$query = $this->queryBuilder->getCreateQuery($rows);
		try {
			$ids = $this->connection->execute($query);
		} catch (BadQueryException $exception) {
			throw new BadQueryException('Unable to insert entities. Bad query \''.$query.'\' has been executed.', 500, $exception);
		} catch (DatabaseManagementSystemException $exception) {
			throw new DatabaseManagementSystemException('Unable to insert entities. Database management system failure has been encountered.', 500, $exception);
		}
		$this->connection->clearCachedQueryResults();

		if (count($ids) !== count($entities)) {
			trigger_error('Unable to update entity id property with actual inserted id. Id was not provided by database system.', E_USER_ERROR);
			return;
		}

		$restrictionClass = $this->mapper->getRestrictionClass(get_class($this));
		$restriction = $this->createRestriction($restrictionClass);
		$restriction->id = Expression::create(Expression::OPERATOR_IS_IN, $ids);

		foreach ($entities as $index => $entity) {
			$properties = $this->getPropertiesForAttaching($entity, $restriction);
			$properties['id'] = $ids[$index];
			$entity->attach($properties);
		}

		return $ids;
	}

	/**
	 * @param Entity[] $entities
	 * @throws DatabaseManagementSystemException if database management system fails
	 * @todo persist inner entities
	 */
	public function persistEntities(&$entities)
	{
		if (empty($entities)) {
			return array();
		}

		$entitiesToInsert = array();
		$entitiesToUpdate = array();
		foreach ($entities as $key => $entity) {
			if ($entity->isNew()) {
				// todo check also inner entities
				$entitiesToInsert[$key]= $entity;
			} elseif ($entity->isChanged()) {
				$entitiesToUpdate[$key] = $entity;
			}
		}

		if (!empty($entitiesToInsert)) {
			try {
				$this->insertEntities($entitiesToInsert);
			} catch (BadQueryException $exception) {
				trigger_error($exception->getMessage(), E_USER_ERROR);
				return;
			}
		}

		if (!empty($entitiesToUpdate)) {
			try {
				$this->updateEntities($entitiesToUpdate);
			} catch (BadQueryException $exception) {
				trigger_error($exception->getMessage(), E_USER_ERROR);
				return;
			}
		}

		$entities = array_merge($entitiesToInsert, $entitiesToUpdate);
	}

	/**
	 * @param Entity[] $entities
	 * @return int updated row count
	 * @throws BadQueryException if bad query was generated
	 * @throws DatabaseManagementSystemException if database management system fails
	 */
	private function updateEntities(&$entities)
	{
		$rows = array();
		$rowClass = $this->mapper->getRowClass(get_class($this), get_class($this->connection));
		if (!is_a($rowClass, Row::class, true)) {
			trigger_error('Unable to create row of \''.static::class.'\' repository. Mapper returns \''.$rowClass.'\' which is not instance of \''.Row::class.'\'.', E_USER_ERROR);
			return 0;
		}
		foreach ($entities as $entity) {
			try {
				$rows[] = call_user_func_array(array($rowClass, 'createFromEntity'), array($entity)); // This should contain only changed properties
			} catch (UnknownMappingException $exception) {
				trigger_error($exception->getMessage(), E_USER_ERROR);
				return;
			}
			$entity->attach(array()); // Attach properties the way they are
		}
		$query = $this->queryBuilder->getUpdateQuery($rows);
		try {
			$rowCount = $this->connection->execute($query);
		} catch (BadQueryException $exception) {
			throw new BadQueryException('Unable to update entities. Bad query \''.$query.'\' has been executed.', 500, $exception);
		} catch (DatabaseManagementSystemException $exception) {
			throw new DatabaseManagementSystemException('Unable to update entities. Database management system failure has been encountered.', 500, $exception);
		}

		$this->connection->clearCachedQueryResults();

		return $rowCount;
	}
}
