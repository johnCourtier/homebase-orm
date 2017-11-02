<?php

namespace Homebase\Model\ORM;

use Examples\Book\Entity as BookEntity;
use Examples\Book\MySQL\Row as BookMySQLRow;
use Tester\Assert;
use Tester\TestCase;

require substr(__DIR__, 0, strrpos(__DIR__, 'tests')+5) . '/../vendor/autoload.php';

class IdentityRowTest extends TestCase
{
	/**
	 * @test
	 */
	public function createFromQueryResultTest()
	{
		$bookMySQLRow = BookMySQLRow::createFromQueryResult(array(
			'id' => 1,
			'title' => 'Necronomicon',
			'authorId' => 666
		));

		Assert::same(1, $bookMySQLRow->id);
		Assert::same('Necronomicon', $bookMySQLRow->title);
		Assert::same(666, $bookMySQLRow->authorId);
	}

	/**
	 * @test
	 */
	public function createFromEntity()
	{
		$bookEntity = BookEntity::createNew(array(
			'id' => 1,
			'title' => 'Necronomicon',
			'authorId' => 666
		));
		$bookMySQLRow = BookMySQLRow::createFromEntity($bookEntity);

		Assert::same(1, $bookMySQLRow->id);
		Assert::same('Necronomicon', $bookMySQLRow->title);
		Assert::same(666, $bookMySQLRow->authorId);
	}
}
