<?php

namespace Homebase\Model\ORM;

use Tester\Assert;
use Tester\TestCase;
use Mockery;
use Examples\Book\Repository as BookRepository;
use Examples\Book\Restriction as BookRestriction;
use Examples\Author\Restriction as AuthorRestriction;

require substr(__DIR__, 0, strrpos(__DIR__, 'tests')+5) . '/../vendor/autoload.php';

class RepositoryTest extends TestCase
{
	/** @var BookRepository */
	protected $bookRepository;

	/** @var Connection */
	protected $connection;

	/** @var QueryBuilder */
	protected $queryBuilder;

	public function setUp()
	{
		$this->connection = Mockery::namedMock(__NAMESPACE__.'\\MySQL\\Connection', Connection::class);
		$this->queryBuilder = Mockery::mock(QueryBuilder::class);
		Assert::noError(function() {
			$this->bookRepository = new BookRepository($this->connection, new NamespaceMapper(array(
				'Examples\\Book' => 'book',
				'Examples\\Author' => 'author'
			)), $this->queryBuilder);
		});
	}

	public function tearDown()
	{
		parent::tearDown();
		Mockery::close();
	}

	/**
	 * @testCase
	 * @return \Examples\Book\Entity
	 */
	public function testFindEntities()
	{
		$bookRestrictions = array(BookRestriction::create());

		$this->queryBuilder->shouldReceive('getRetrieveQuery')
			->once()
			->with($bookRestrictions)
			->andReturn('retrieve query');
		$bookQueryResults = array(
			array('id' => 1, 'title' => 'Necronomicon', 'authorId' => 666)
		);
		$this->connection->shouldReceive('execute')
			->once() // Execution of same query should be done only once
			->with('retrieve query')
			->andReturn($bookQueryResults);

		$bookEntities = $this->bookRepository->findEntities($bookRestrictions);

		Assert::count(count($bookQueryResults), $bookEntities, 'Retrieved entity count does not match row count.');
		/* @var $bookEntity \Examples\Book\Entity */
		$bookEntity = reset($bookEntities);

		Assert::same(1, $bookEntity->id, 'Entity id does not match row id.');
		Assert::same('Necronomicon', $bookEntity->title, 'Entity title property does not match mapped row property.');
		Assert::same(666, $bookEntity->authorId);
		Assert::false($bookEntity->isNew(), 'Entity from database system should not act as new.');

		return $bookEntity;
	}

	/**
	 * @testCase
	 */
	public function testRelatedEntities()
	{
		$bookEntity = $this->testFindEntities();

		$necronomiconRestriction = BookRestriction::create();
		$necronomiconRestriction->id = Expression::create(Expression::OPERATOR_IS, 1);
		$authorRestriction = AuthorRestriction::create();
		$authorRestriction->joinRestriction($necronomiconRestriction);
		$authorRestrictions = array($authorRestriction);
		$this->queryBuilder->shouldReceive('getRetrieveQuery')
			->once()
			->with($authorRestrictions)
			->andReturn('author query');
		$authorQueryResults = array(
			array('id' => 666, 'name' => 'Abdul Alhazred', 'birthDate' => '1938', 'bookId' => 1)
		);
		$this->connection->shouldReceive('execute')
			->once() // Execution of same query should be done only once
			->with('author query')
			->andReturn($authorQueryResults);
		$authorEntity = $bookEntity->authorEntity;

		Assert::same(666, $authorEntity->id);
		Assert::same($authorEntity->name, 'Abdul Alhazred');
		Assert::equal($authorEntity->birthDate, new \DateTime('1938'));
		Assert::true(isset($authorEntity->bookEntities), 'Related entities of related entity were not set.');

		$abdulAlhazredRestriction = AuthorRestriction::create();
		$abdulAlhazredRestriction->id = Expression::create(Expression::OPERATOR_IS, 666);
		$bookRestriction = BookRestriction::create();
		$bookRestriction->joinRestriction($abdulAlhazredRestriction);
		$bookRestrictions = array($bookRestriction);

		$this->queryBuilder->shouldReceive('getRetrieveQuery')
			->once()
			->with($bookRestrictions)
			->andReturn('retrieve query');
		$bookQueryResults = array(
			array('id' => 1, 'title' => 'Necronomicon', 'authorId' => 666)
		);
		$this->connection->shouldReceive('execute')
			->once() // Execution of same query should be done only once
			->with('retrieve query')
			->andReturn($bookQueryResults);

		$bookEntities = $authorEntity->bookEntities;
		Assert::count(1, $bookEntities);
		/* @var $necronomicon \Examples\Book\Entity */
		$necronomicon = reset($bookEntities);
		Assert::equal($bookEntity->id, $necronomicon->id, 'Author\'s book has different id');
		Assert::equal($bookEntity->title, $necronomicon->title, 'Author\'s book has different title');
		Assert::equal($bookEntity->authorId, $necronomicon->authorId, 'Author\'s book has different author id');
	}
}

$repositoryTest = new RepositoryTest();
$repositoryTest->run();