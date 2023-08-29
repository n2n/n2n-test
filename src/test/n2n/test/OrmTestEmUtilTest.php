<?php

namespace n2n\test;

use PHPUnit\Framework\TestCase;
use n2n\persistence\meta\data\QueryColumn;
use n2n\persistence\meta\data\QueryConstant;
use n2n\test\bo\N2nTestDummyObject;
use n2n\util\ex\IllegalStateException;

class OrmTestEmUtilTest extends TestCase {
	function setUp(): void {
		TestEnv::db()->truncate();
	}

	private function insertDummyObjectToDb(string $dummyString): void {
		$pdo = TestEnv::db()->pdo();
		$builder = $pdo->getMetaData()->createInsertStatementBuilder();
		$builder->setTable('n2n_test_dummy_object');
		$builder->addColumn(new QueryColumn('dummy_string'), new QueryConstant($dummyString));
		$pdo->exec($builder->toSqlString());
	}

	function testOrmTestTemUtilExpectExceptionBecauseNoTransactionOpen() {
		//transactionalEntityManager
		$this->expectException(IllegalStateException::class);
		TestEnv::temUtil();
	}

	function testOrmTestTemUtil() {
		//transactionalEntityManager
		$this->insertDummyObjectToDb('value');
		$tx = TestEnv::createTransaction(true);
		$this->assertEquals(1, TestEnv::temUtil()->count(N2nTestDummyObject::class));
		$tx->commit();
	}

	function testOrmTestEmUtil() {
		//EntityManager
		$this->insertDummyObjectToDb('value');
		$this->assertEquals(1, TestEnv::emUtil()->count(N2nTestDummyObject::class));
	}

	function testOrmTestEmUtilSearch() {
		//EntityManager
		$this->insertDummyObjectToDb('Anton');
		$this->insertDummyObjectToDb('Sophie');
		$this->insertDummyObjectToDb('Otto');
		$this->insertDummyObjectToDb('Sophie');

		$this->assertEquals(2, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Sophie']));
		$this->assertEquals(1, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Otto']));

		//this is what we have inside DB
		$this->assertEquals(4, TestEnv::emUtil()->count(N2nTestDummyObject::class));
		$values = TestEnv::em()->createSimpleCriteria(N2nTestDummyObject::class)->toQuery()->fetchArray();
		$this->assertEquals('Anton', $values[0]->getDummyString());
		$this->assertEquals('Sophie', $values[1]->getDummyString());
		$this->assertEquals('Otto', $values[2]->getDummyString());
		$this->assertEquals('Sophie', $values[3]->getDummyString());
		$this->assertCount(4, $values);

	}
}