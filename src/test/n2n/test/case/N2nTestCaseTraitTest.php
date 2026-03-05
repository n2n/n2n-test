<?php

namespace n2n\test\case;

use PHPUnit\Framework\TestCase;
use n2n\test\bo\N2nTestMassiveDummyObject;
use n2n\spec\dbo\meta\data\impl\QueryColumn;
use n2n\spec\dbo\meta\data\impl\QueryConstant;
use PHPUnit\Framework\ExpectationFailedException;
use n2n\test\TestEnv;
use n2n\spec\dbo\err\DboException;

class N2nTestCaseTraitTest extends TestCase {
	use N2nTestCaseTrait;

	private N2nTestMassiveDummyObject $dummyObject3;
	private array $dummyObjects;

	/**
	 * @throws DboException
	 */
	function setUp(): void {
		$date = new \DateTime('2025-05-25 15:00:00');

		$dummyObject1 = new N2nTestMassiveDummyObject();
		$dummyObject1->setId(1);
		$dummyObject1->setDummyString('value');
		$dummyObject1->setDateTime($date);

		$dummyObject2 = new N2nTestMassiveDummyObject();
		$dummyObject2->setId(2);
		$dummyObject2->setDummyString('blubb');
		$dummyObject2->setDateTime($date);

		$this->dummyObject3 = new N2nTestMassiveDummyObject();
		$this->dummyObject3->setId(3);
		$this->dummyObject3->setDummyString('holeradio');
		$this->dummyObject3->setDateTime($date);
		$this->dummyObject3->setFloat(5.5);
		$this->dummyObject3->setInt(99);

		$this->dummyObjects = [$dummyObject1, $dummyObject2, $this->dummyObject3];
	}

	/**
	 * Only here to show how the Default assertEquals and assertSame works in difference to assertTypeSafeEquals
	 * assertEquals don't care about type, test is true as long value are equals
	 * see {@link self::testSame()} and {@link self::testTypeSafeEquals()}
	 */
	function testEquals() {
		// on simple string all do the same
		$this->assertEquals('holeradio', $this->dummyObject3->getDummyString());
		// assertEquals don't care about object reference or Type as long Values are the same
		$this->assertEquals(new \DateTimeImmutable('2025-05-25 15:00:00'), $this->dummyObject3->getDateTime());
		// assertEquals don't care about type if values are equals
		$this->assertEquals('99', $this->dummyObject3->getInt());
		// assertEquals don't care about type if values are equals
		$this->assertEquals('5.5', $this->dummyObject3->getFloat());
	}

	/**
	 * Only here to show how the Default assertEquals and assertSame works in difference to assertTypeSafeEquals
	 * assertSame care about type and also about object-reference
	 * see {@link self::testEquals()} and {@link self::testTypeSafeEquals()}
	 */
	function testSame() {
		// on simple string all do the same
		$this->assertSame('holeradio', $this->dummyObject3->getDummyString());
		try {
			$this->assertSame(new \DateTime('2025-05-25 15:00:00'), $this->dummyObject3->getDateTime());
			$this->fail('should not happen, assertSame should fail');
		} catch (ExpectationFailedException $e) {
			// all fine, exception is expected, fails because not identical object
			$this->assertNotEmpty($e->getMessage());
		}
		try {
			$this->assertSame('99', $this->dummyObject3->getInt());
			$this->fail('should not happen, assertSame should fail');
		} catch (ExpectationFailedException $e) {
			// all fine, exception is expected because not same type
			$this->assertNotEmpty($e->getMessage());
		}
		try {
			$this->assertSame('5.5', $this->dummyObject3->getFloat());
			$this->fail('should not happen, assertSame should fail');
		} catch (ExpectationFailedException $e) {
			// all fine, exception is expected because not same type
			$this->assertNotEmpty($e->getMessage());
		}
	}

	/**
	 * assertTypeSafeEquals care about type but not about object-reference
	 * see {@link self::testEquals()} and {@link self::testSame()}
	 */
	function testTypeSafeEquals() {
		// on simple string all do the same
		$this->assertTypeSafeEquals('holeradio', $this->dummyObject3->getDummyString());
		// this is true, it is not identical object, but from same type and has same values, this is ok for assertTypeSafeEquals
		$this->assertTypeSafeEquals(new \DateTime('2025-05-25 15:00:00'), $this->dummyObject3->getDateTime());
		try {
			$this->assertTypeSafeEquals(new \DateTimeImmutable('2025-05-25 15:00:00'), $this->dummyObject3->getDateTime());
			$this->fail('should not happen, assertTypeSafeEquals should fail');
		} catch (ExpectationFailedException $e) {
			// all fine, exception is expected because not same type
			$this->assertNotEmpty($e->getMessage());
		}
		try {
			$this->assertTypeSafeEquals('99', $this->dummyObject3->getInt());
			$this->fail('should not happen, assertTypeSafeEquals should fail');
		} catch (ExpectationFailedException $e) {
			// all fine, exception is expected because not same type
			$this->assertNotEmpty($e->getMessage());
		}
		try {
			$this->assertTypeSafeEquals('5.5', $this->dummyObject3->getFloat());
			$this->fail('should not happen, assertTypeSafeEquals should fail');
		} catch (ExpectationFailedException $e) {
			// all fine, exception is expected because not same type
			$this->assertNotEmpty($e->getMessage());
		}
	}

	/**
	 * assertArrayContainsSubset allows typesafe check of values, without the need to write down all params
	 * this can help if you have a big array and only need to check a few critical values, and skip the rest
	 * Should only be used when other Tests cover this! and one line is tested full
	 * all given keys are tested, and all given values are type-strict tested too
	 */
	function testArrayHasSubset() {
		//assertArrayContainsSubset need an array
		$arrayOfArrays = array_map(fn($obj) => $obj->jsonSerialize(), $this->dummyObjects);
		$expectedSubset = [
				0 => ['dummyString' => 'value', 'float' => 3.14159265],
				1 => ['dummyString' => 'blubb', 'int' => 42],
				2 => ['dummyString' => 'holeradio', 'int' => 99, 'float' => 5.5],
		];

		//always check a line full
		$this->assertSame( ['id' => $this->dummyObject3->getId(), 'dummyString' => 'holeradio', 'int' => 99, 'float' => 5.5, 'dateTime' => '2025-05-25 15:00:00'], $arrayOfArrays[2]);
		//rest could be checked partially
		$this->assertArrayContainsSubset($expectedSubset, $arrayOfArrays);

		// sure it is basically the same as you would do with code below, but assertArrayContainsSubset is more compact
		$this->assertSame('value', $this->dummyObjects[0]->getDummyString());
		$this->assertSame(3.14159265, $this->dummyObjects[0]->getFloat());
		$this->assertSame('blubb', $this->dummyObjects[1]->getDummyString());
		$this->assertSame(42, $this->dummyObjects[1]->getInt());
		$this->assertSame('holeradio', $this->dummyObjects[2]->getDummyString());
		$this->assertSame(99, $this->dummyObjects[2]->getInt());
		$this->assertSame(5.5, $this->dummyObjects[2]->getFloat());
	}

	function testArrayHasSubsetFailBecauseValueMismatch() {

		$this->expectException(ExpectationFailedException::class);
		$this->expectExceptionMessageMatches('~contains the expected subset~');
		$this->expectExceptionMessageMatches('~Value mismatch at~');

		$arrayOfArrays = array_map(fn($obj) => $obj->jsonSerialize(), $this->dummyObjects);
		$expectedSubset = [
				0 => ['dummyString' => 'value', 'float' => 3.14159265],
				1 => ['dummyString' => 'blubb', 'int' => '42'],
				2 => ['dummyString' => 'holeradio', 'int' => 99, 'float' => 5.5],
		];

		$this->assertArrayContainsSubset($expectedSubset, $arrayOfArrays);
	}

	function testArrayHasSubsetFailBecauseKeyMismatch() {
		$this->expectException(ExpectationFailedException::class);
		$this->expectExceptionMessageMatches('~contains the expected subset~');
		$this->expectExceptionMessageMatches('~Missing key~');

		$arrayOfArrays = array_map(fn($obj) => $obj->jsonSerialize(), $this->dummyObjects);
		$expectedSubset = [
				0 => ['dummyString' => 'value', 'float' => 3.14159265],
				1 => ['dummyString' => 'blubb', 'integer' => 42],
				2 => ['dummyString' => 'holeradio', 'int' => 99, 'float' => 5.5],
		];

		$this->assertArrayContainsSubset($expectedSubset, $arrayOfArrays);
	}

	function testArrayHasSubsetFailBecauseTypeMismatch() {
		$this->expectException(ExpectationFailedException::class);
		$this->expectExceptionMessageMatches('~contains the expected subset~');
		$this->expectExceptionMessageMatches('~Type mismatch~');

		$expectedSubset = [
				0 => ['dummyString' => 'value', 'float' => 3.14159265],
				1 => ['dummyString' => 'blubb', 'int' => 42.0],
				2 => ['dummyString' => 'holeradio', 'int' => 99, 'float' => 5.5],
		];

		$this->assertArrayContainsSubset($expectedSubset, $this->dummyObjects);
	}

	function testArrayHasSubsetFailBecauseMissingKey() {
		$this->expectException(ExpectationFailedException::class);
		$this->expectExceptionMessageMatches('~contains the expected subset~');
		$this->expectExceptionMessageMatches('~Missing key~');
		$arrayOfArrays = array_map(fn($obj) => $obj->jsonSerialize(), $this->dummyObjects);

		$expectedSubset = [
				0 => ['dummyString' => 'value', 'float' => 3.14159265],
				2 => ['dummyString' => 'holeradio', 'int' => 99, 'float' => 5.5],
				4 => ['dummyString' => 'blubb', 'int' => 42],
		];

		$this->assertArrayContainsSubset($expectedSubset, $arrayOfArrays);
	}
}
