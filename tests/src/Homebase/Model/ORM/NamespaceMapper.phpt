<?php

namespace Homebase\Model\ORM;

use Tester\Assert;
use Tester\TestCase;

require substr(__DIR__, 0, strrpos(__DIR__, 'tests')+5) . '/../vendor/autoload.php';

class NamespaceMapperTest  extends TestCase
{
	/** @var NamespaceMapper */
	protected $namespaceMapper;

	public function setUp()
	{
		$namespaceMap = array();
		$this->namespaceMapper = new NamespaceMapper($namespaceMap);
	}

	/**
	 * @test
	 */
	public function testGetEntityClass()
	{
		$entityClass = $this->namespaceMapper->getEntityClass(Repository::class);
		Assert::equal(Entity::class, $entityClass);
	}

	/**
	 * @test
	 */
	public function testGetRestrictionClass()
	{
		$restrictionClass = $this->namespaceMapper->getRestrictionClass(Repository::class);
		Assert::equal(Restriction::class, $restrictionClass);
	}

	/**
	 * @test
	 */
	public function testGetRowClass()
	{
		Assert::error(function() {
			$rowClass = $this->namespaceMapper->getRowClass(Repository::class, __NAMESPACE__.'\\MySQL\\Connection');
			Assert::equal(__NAMESPACE__.'\\MySQL\\Row', $rowClass);
		}, E_USER_ERROR, 'Unable to get row class. Mapped class \'Homebase\Model\ORM\MySQL\Row\' is not an instance of \''.Row::class.'\'.');
	}
}

$namespaceMapperTest = new NamespaceMapperTest();
$namespaceMapperTest->run();