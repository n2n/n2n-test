<?php

namespace n2n\test;

use PHPUnit\Framework\TestCase;
use n2n\spec\dbo\meta\data\impl\QueryColumn;
use n2n\spec\dbo\meta\data\impl\QueryConstant;
use n2n\test\bo\N2nTestDummyObject;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\TransactionRequiredException;
use n2n\reflection\property\InaccessiblePropertyException;
use n2n\reflection\property\UnknownPropertyException;
use n2n\reflection\property\InvalidPropertyAccessMethodException;
use n2n\reflection\property\PropertyAccessException;
use n2n\persistence\orm\CorruptedDataException;
use n2n\spec\dbo\err\DboException;

class OrmTestEmUtilTest extends TestCase {
	function setUp(): void {
		if (TestEnv::container()->tm()->hasOpenTransaction()) {
			TestEnv::container()->tm()->getRootTransaction()->rollBack();
		}

		TestEnv::em()->clear();
		TestEnv::db()->truncate();
	}

	/**
	 * @throws DboException
	 */
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

	/**
	 * @throws DboException
	 */
	function testOrmTestTemUtilCount() {
		//transactionalEntityManager
		$this->insertDummyObjectToDb('value');
		$tx = TestEnv::createTransaction(true);
		$this->assertEquals(1, TestEnv::temUtil()->count(N2nTestDummyObject::class));
		$tx->commit();
	}

	/**
	 * @throws DboException
	 */
	function testOrmTestEmUtilCount() {
		//EntityManager
		$this->insertDummyObjectToDb('value');
		$this->assertEquals(1, TestEnv::emUtil()->count(N2nTestDummyObject::class));
	}

	/**
	 * @throws DboException
	 */
	function testOrmTestEmUtilCountSearchMatches() {
		//EntityManager
		$this->insertDummyObjectToDb('Anton');
		$this->insertDummyObjectToDb('Sophie');
		$this->insertDummyObjectToDb('Otto');
		$this->insertDummyObjectToDb('Sophie');

		$this->assertEquals(2, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Sophie']));
		$this->assertEquals(1, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Otto']));
		$this->assertEquals(0, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Charlie']));

		//this is what we have inside DB
		$this->assertEquals(4, TestEnv::emUtil()->count(N2nTestDummyObject::class));
		$values = TestEnv::em()->createSimpleCriteria(N2nTestDummyObject::class)->toQuery()->fetchArray();
		$this->assertEquals('Anton', $values[0]->getDummyString());
		$this->assertEquals('Sophie', $values[1]->getDummyString());
		$this->assertEquals('Otto', $values[2]->getDummyString());
		$this->assertEquals('Sophie', $values[3]->getDummyString());
		$this->assertCount(4, $values);

	}

	/**
	 * @throws DboException
	 */
	function testOrmTestEmUtilDeleteSearchMatches() {
		//EntityManager
		$this->insertDummyObjectToDb('Anton');
		$this->insertDummyObjectToDb('Sophie');
		$this->insertDummyObjectToDb('Otto');
		$this->insertDummyObjectToDb('Sophie');

		$this->assertEquals(2, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Sophie']));
		$this->assertEquals(1, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Otto']));
		$this->assertEquals(0, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Charlie']));
		$tx = TestEnv::createTransaction();
		TestEnv::emUtil()->delete(N2nTestDummyObject::class, ['dummyString' => 'Sophie']);
		$tx->commit();
		$this->assertEquals(0, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Sophie']));

		//this is what we have inside DB
		$this->assertEquals(2, TestEnv::emUtil()->count(N2nTestDummyObject::class));
		$values = TestEnv::em()->createSimpleCriteria(N2nTestDummyObject::class)->toQuery()->fetchArray();
		$this->assertEquals('Anton', $values[0]->getDummyString());
		$this->assertEquals('Otto', $values[1]->getDummyString());
		$this->assertCount(2, $values);
	}

	/**
	 * @throws DboException
	 */
	function testOrmTestEmUtilDeleteExpectBecauseNoTransactionOpen() {
		$this->expectException(TransactionRequiredException::class);
		$this->insertDummyObjectToDb('Sophie');
		TestEnv::emUtil()->delete(N2nTestDummyObject::class, ['dummyString' => 'Sophie']);
	}

	/**
	 * @throws DboException
	 */
	function testOrmTestEmUtilDeleteExpectBecauseTransactionOpenIsReadonly() {
		$this->expectException(IllegalStateException::class);
		$this->insertDummyObjectToDb('Sophie');
		$tx = TestEnv::createTransaction(true);
		TestEnv::emUtil()->delete(N2nTestDummyObject::class, ['dummyString' => 'Sophie']);
		$tx->commit();
	}

	/**
	 * @throws InaccessiblePropertyException
	 * @throws UnknownPropertyException
	 * @throws InvalidPropertyAccessMethodException
	 * @throws PropertyAccessException
	 * @throws CorruptedDataException
	 * @throws DboException
	 */
	function testOrmTestEmUtilUpdateSearchMatches() {
		//EntityManager
		$this->insertDummyObjectToDb('Anton');
		$this->insertDummyObjectToDb('Sophie');
		$this->insertDummyObjectToDb('Otto');
		$this->insertDummyObjectToDb('Sophie');

		$this->assertEquals(2, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Sophie']));
		$this->assertEquals(1, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Otto']));
		$this->assertEquals(0, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Charlie']));
		$tx = TestEnv::createTransaction();
		TestEnv::emUtil()->update(N2nTestDummyObject::class, ['dummyString' => 'Charlie'], ['dummyString' => 'Sophie']);
		$tx->commit();
		$this->assertEquals(0, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Sophie']));
		$this->assertEquals(2, TestEnv::emUtil()->count(N2nTestDummyObject::class, ['dummyString' => 'Charlie']));

		//this is what we have inside DB
		$this->assertEquals(4, TestEnv::emUtil()->count(N2nTestDummyObject::class));
		$values = TestEnv::em()->createSimpleCriteria(N2nTestDummyObject::class)->toQuery()->fetchArray();
		$this->assertEquals('Anton', $values[0]->getDummyString());
		$this->assertEquals('Charlie', $values[1]->getDummyString());
		$this->assertEquals('Otto', $values[2]->getDummyString());
		$this->assertEquals('Charlie', $values[3]->getDummyString());
		$this->assertCount(4, $values);
	}

	/**
	 * @throws InaccessiblePropertyException
	 * @throws UnknownPropertyException
	 * @throws InvalidPropertyAccessMethodException
	 * @throws PropertyAccessException
	 * @throws CorruptedDataException
	 * @throws DboException
	 */
	function testOrmTestEmUtilUpdateExpectBecauseNoTransactionOpen() {
		$this->expectException(TransactionRequiredException::class);
		$this->insertDummyObjectToDb('Sophie');
		TestEnv::emUtil()->update(N2nTestDummyObject::class, ['dummyString' => 'Charlie'], ['dummyString' => 'Sophie']);
	}

	/**
	 * @throws InaccessiblePropertyException
	 * @throws UnknownPropertyException
	 * @throws InvalidPropertyAccessMethodException
	 * @throws PropertyAccessException
	 * @throws CorruptedDataException
	 * @throws DboException
	 */
	function testOrmTestEmUtilUpdateExpectBecauseTransactionOpenIsReadonly() {
		$this->expectException(IllegalStateException::class);
		$this->insertDummyObjectToDb('Sophie');
		$tx = TestEnv::createTransaction(true);
		TestEnv::emUtil()->update(N2nTestDummyObject::class, ['dummyString' => 'Charlie'], ['dummyString' => 'Sophie']);
		$tx->commit();
	}

}