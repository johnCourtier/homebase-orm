<?php

namespace Foo\MySQL
{
	use Homebase\Model\ORM\Row as BasicRow;
	use Homebase\Model\ORM\Entity;
	use Homebase\Model\ORM\UnknownMappingException;

	require substr(__DIR__, 0, strrpos(__DIR__, 'tests')+5) . '/../vendor/autoload.php';

	/**
	 * @property int $rowId
	 * @property string $title
	 */
	class Row extends BasicRow
	{
		/**
		 * {@inheritDoc}
		 */
		protected function getPropertiesFromEntity(Entity $entity)
		{
			if ($entity instanceOf \Foo\Entity) {
				$values = array(
					'title' => $entity->title
				);

				if (!$entity->isNew()) {
					$values['rowId'] = $entity->id;
				}

				return $values;
			}

			throw new UnknownMappingException('Unable to get properties from \''.get_class($entity).'\'. Mapping is not defined.');
		}

		/**
		 * {@inheritDoc}
		 */
		protected function getPropertiesFromQueryResult(array $queryResult)
		{
			return array(
				'rowId' => $queryResult['id'],
				'title' => $queryResult['title']
			);
		}
	}
}

namespace Foo
{
	use Homebase\Model\ORM\Entity as BasicEntity;
	use Homebase\Model\ValueObject;
	use Foo\MySQL\Row;
	use Homebase\Model\ORM\UnknownMappingException;
	use Homebase\Model\ORM\Restriction as BasicRestriction;
	use Homebase\Model\ORM\Repository as BasicRepository;

	/**
	 * @property string $title
	 */
	class Entity extends BasicEntity
	{
		/**
		 * {@inheritDoc}
		 */
		protected function getPropertiesFromValueObject(ValueObject $valueObject)
		{
			if ($valueObject instanceOf Row) {
				return array(
					'id' => $valueObject->rowId,
					'title' => $valueObject->title
				);
			}

			throw new UnknownMappingException('Unable to get properties from \''.get_class($valueObject).'\'. Mapping is not defined.');
		}
	}

	class Restriction extends BasicRestriction
	{

	}

	/**
	 * @property string $property
	 */
	class Repository extends BasicRepository
	{
		/**
		 * @param Restriction[] $restrictions
		 * @return Entity[]
		 */
		public function findEntities($restrictions)
		{
			return parent::findEntities($restrictions);
		}
	}
}

namespace Homebase\Model\ORM
{
	use Tester\Assert;
	use Tester\TestCase;
	use Mockery;

	class RepositoryTest extends TestCase
	{
		/** @var \Foo\Repository */
		protected $repository;

		/** @var Connection */
		protected $connection;

		/** @var Mapper */
		protected $mapper;

		/** @var QueryBuilder */
		protected $queryBuilder;

		public function setUp()
		{
			$this->connection = Mockery::namedMock(__NAMESPACE__.'\\MySQL\\Connection', Connection::class);
			$namespaceMap = array();
			$this->mapper = new NamespaceMapper($namespaceMap);
			$this->queryBuilder = Mockery::mock(QueryBuilder::class);
			Assert::noError(function() {
				$this->repository = new \Foo\Repository($this->connection, $this->mapper, $this->queryBuilder);
			});
		}

		public function tearDown()
		{
			parent::tearDown();
			Mockery::close();
		}

		/**
		 * @testCase
		 */
		public function testFindEntities()
		{
			$restrictions = array(Mockery::mock(Restriction::class));

			$this->queryBuilder->shouldReceive('getRetrieveQuery')
				->twice()
				->with($restrictions)
				->andReturn('retrieve query');
			$queryResults = array(
				array('id' => 1, 'title' => 'mockedName')
			);
			$this->connection->shouldReceive('execute')
				->twice()
				->with('retrieve query')
				->andReturn($queryResults);

			$entities = $this->repository->findEntities($restrictions);

			Assert::count(count($queryResults), $entities, 'Retrieved entity count does not match row count.');
			$entity = reset($entities);

			Assert::same(1, $entity->id, 'Entity id does not match row id.');
			Assert::same('mockedName', $entity->title, 'Entity title property does not match mapped row property.');
			Assert::false($entity->isNew(), 'Entity from database system should not act as new.');

			$entities = $this->repository->findEntities($restrictions);
		}

		/**
		 * @testCase
		 */
		public function testDeleteEntities()
		{
			$restrictions = array(Mockery::mock(Restriction::class));

			$this->queryBuilder->shouldReceive('getDeleteQuery')
				->once()
				->with($restrictions)
				->andReturn('delete query');
			$this->connection->shouldReceive('execute')
				->with('delete query')
				->andReturn(5);
			$this->connection->shouldReceive('clearCachedQueryResults')
				->once();

			$rowCount = $this->repository->deleteEntities($restrictions);

			Assert::same(5, $rowCount, 'Deleted row count does not match expected count.');
		}

		/**
		 * @testCase
		 */
		public function testPersistNewEntity()
		{
			$entity = \Foo\Entity::createNew(array(
				'title' => 'new title'
			));
			$entities = array($entity);

			$newRow = \Foo\MySQL\Row::createFromEntity($entity, 'myTable');
			$this->queryBuilder->shouldReceive('getCreateQuery')
				->once()
				->with(array($newRow))
				->andReturn('create query');
			$this->connection->shouldReceive('execute')
				->once()
				->with('create query')
				->andReturn(array(
					2 // new inserted id
				));
			$this->connection->shouldReceive('clearCachedQueryResults');

			$this->repository->persistEntities($entities);
			Assert::equal(2, $entity->id, 'Entity was not attached properly. Entity id is different.');
			Assert::false($entity->isNew(), 'Entity was not persisted correctly. Still marked as new.');
			Assert::false($entity->isChanged(), 'Entity was not persisted correctly. Still marked as changed.');
		}

		/**
		 * @testCase
		 */
		public function testPersistExistingEntity()
		{
			$row = \Foo\MySQL\Row::createFromQueryResult(array('id' => 3, 'title' => 'original title'), 'myTable');
			$entity = \Foo\Entity::createFromValueObject($row);
			Assert::false($entity->isChanged(), 'Entity value assigning is done multiple times and entity acts as changed.');
			$entity->title = 'changed title';
			Assert::true($entity->isChanged(), 'Entity property changing is not recognized.');
			$entities = array($entity);

			$rowToUpdate = \Foo\MySQL\Row::createFromEntity($entity, 'myTable');
			$this->queryBuilder->shouldReceive('getUpdateQuery')
				->once()
				->with(array($rowToUpdate))
				->andReturn('update query');
			$this->connection->shouldReceive('execute')
				->once()
				->with('update query')
				->andReturn(array(
					1 // updated row count
				));
			$this->connection->shouldReceive('clearCachedQueryResults');

			$this->repository->persistEntities($entities);
			Assert::equal(3, $entity->id, 'Entity was not attached properly. Entity id is different.');
			Assert::equal('changed title', $entity->title, 'Entity was not attached properly. Entity title is different.');
			Assert::false($entity->isNew(), 'Entity was not persisted correctly. Still marked as new.');
			Assert::false($entity->isChanged(), 'Entity was not persisted correctly. Still marked as changed.');
		}

		/**
		 * @testCase
		 */
		public function testPersistUnchangedEntity()
		{
			$row = \Foo\MySQL\Row::createFromQueryResult(array('id' => 3, 'title' => 'original title'), 'myTable');
			$entity = \Foo\Entity::createFromValueObject($row);
			Assert::false($entity->isChanged(), 'Entity value assigning is done multiple times and entity acts as changed.');
			$entities = array($entity);
			$this->repository->persistEntities($entities);
			Assert::equal(\Foo\Entity::createFromValueObject($row), $entity);
		}
	}

	$repositoryTest = new RepositoryTest();
	$repositoryTest->run();
}