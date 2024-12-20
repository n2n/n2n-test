<?php

namespace n2n\test;

use PHPUnit\Framework\TestCase;
use n2n\spec\dbo\meta\data\impl\QueryColumn;
use n2n\spec\dbo\meta\data\impl\QueryConstant;
use n2n\spec\dbo\meta\data\impl\QueryTable;

class DbTestPdoUtilTest extends TestCase {
	function setUp(): void {
		TestEnv::db()->truncate();
		$this->insertTempDataToDb('aGoodName');
		$this->insertTempDataToDb('aWrongName', 'new');
		$this->insertTempDataToDb('aFunnyName');
		$this->insertTempDataToDb('aNormalName');
		$this->insertTempDataToDb('aNewName');
		$this->insertTempDataToDb('aNewName', 'new');
	}

	private function insertTempDataToDb(string $valueName, ?string $valueStatus = 'initial'): void {
		$pdo = TestEnv::db()->pdo();
		$builder = $pdo->getMetaData()->createInsertStatementBuilder();
		$builder->setTable('n2n_test_tbl');
		$builder->addColumn(new QueryColumn('value_name'), new QueryConstant($valueName));
		$builder->addColumn(new QueryColumn('value_status'), new QueryConstant($valueStatus));
		$pdo->exec($builder->toSqlString());
	}

	private function selectTempDataFromDb(string $column, int|string $value, bool $fetchAll = true): mixed {
		$pdo = TestEnv::db()->pdo();
		$builder = $pdo->getMetaData()->createSelectStatementBuilder();
		$builder->addFrom(new QueryTable('n2n_test_tbl'));
		$builder->addSelectColumn(new QueryColumn('id'));
		$builder->addSelectColumn(new QueryColumn('value_name'));
		$builder->addSelectColumn(new QueryColumn('value_status'));
		$builder->getWhereComparator()->match(new QueryColumn($column), '=', new QueryConstant($value));

		$stmt = $pdo->prepare($builder->toSqlString());
		$stmt->execute();
		if ($fetchAll === false) {
			return $stmt->fetch(\PDO::FETCH_ASSOC);
		}
		return ($stmt->fetchAll(\PDO::FETCH_ASSOC));
	}

	function testUpdateReplaceValueMatchedById() {
		//select before use pdoUtil()->update
		$this->assertEquals('aWrongName', $this->selectTempDataFromDb('id', 2)[0]['value_name']);
		$this->assertEquals(2, $this->selectTempDataFromDb('id', 2)[0]['id']);

		//we can use a different column as match for the updated value
		TestEnv::pdoUtil()->update('n2n_test_tbl', ['value_name' => 'theCorrectName'], ['id' => 2]);

		//select after use pdoUtil()->update
		$this->assertEquals('theCorrectName', $this->selectTempDataFromDb('id', 2)[0]['value_name']);
		$this->assertEquals(2, $this->selectTempDataFromDb('id', 2)[0]['id']);
		$this->assertCount(1, $this->selectTempDataFromDb('value_name', 'theCorrectName'));
		//a select to ensure nothing changes here
		$this->assertEquals('aGoodName', $this->selectTempDataFromDb('value_name', 'aGoodName')[0]['value_name']);
		$this->assertEquals(1, $this->selectTempDataFromDb('id', 1)[0]['id']);
	}

	function testUpdateReplaceValueMatchedByValue() {
		//select before use pdoUtil()->update
		$this->assertEquals('aWrongName', $this->selectTempDataFromDb('value_name', 'aWrongName')[0]['value_name']);
		$idBefore = $this->selectTempDataFromDb('value_name', 'aWrongName')[0]['id'];

		//we can use the value we like to update as matches
		TestEnv::pdoUtil()->update('n2n_test_tbl', ['value_name' => 'theCorrectName'], ['value_name' => 'aWrongName']);

		//select after use pdoUtil()->update
		$this->assertEquals('theCorrectName', $this->selectTempDataFromDb('value_name', 'theCorrectName')[0]['value_name']);
		$idAfter = $this->selectTempDataFromDb('value_name', 'theCorrectName')[0]['id'];
		$this->assertEquals($idBefore, $idAfter);
		$this->assertCount(1, $this->selectTempDataFromDb('value_name', 'theCorrectName'));
		//a select to show previous value did not exist anymore
		$this->assertEmpty($this->selectTempDataFromDb('value_name', 'aWrongName'));
		//a select to ensure nothing changes here
		$this->assertEquals('aGoodName', $this->selectTempDataFromDb('value_name', 'aGoodName')[0]['value_name']);
	}

	function testUpdateReplaceValuesEmptyMatches() {
		//select before use pdoUtil()->update
		$this->assertEquals('aWrongName', $this->selectTempDataFromDb('value_name', 'aWrongName')[0]['value_name']);

		//without or an empty a matches selector, all values of given column are affected
		TestEnv::pdoUtil()->update('n2n_test_tbl', ['value_name' => 'theCorrectName']);

		//select after use pdoUtil()->update
		$dbSelect = $this->selectTempDataFromDb('value_name', 'theCorrectName');
		$this->assertCount(6, $dbSelect);
		$this->assertEquals('theCorrectName', $dbSelect[0]['value_name']);
		$this->assertEquals('theCorrectName', $dbSelect[1]['value_name']);
		$this->assertEquals('theCorrectName', $dbSelect[2]['value_name']);
		$this->assertEquals('theCorrectName', $dbSelect[3]['value_name']);
		$this->assertEquals('theCorrectName', $dbSelect[4]['value_name']);
		$this->assertEquals('theCorrectName', $dbSelect[5]['value_name']);
		//a select to show previous value did not exist anymore
		$this->assertEmpty($this->selectTempDataFromDb('value_name', 'aGoodName'));
	}

	function testUpdateReplaceMultipleValues() {
		//select before use pdoUtil()->update
		$dbSelect = $this->selectTempDataFromDb('value_name', 'aWrongName');
		$this->assertEquals('aWrongName', $dbSelect[0]['value_name']);
		$this->assertEquals('new', $dbSelect[0]['value_status']);
		$idBefore = $dbSelect[0]['id'];

		//we can update multiple columns of the same row inside the same update Statement
		TestEnv::pdoUtil()->update('n2n_test_tbl',
				['value_name' => 'theCorrectName', 'value_status' => 'changed'], ['value_name' => 'aWrongName']);

		//select after use pdoUtil()->update
		$dbSelectNew = $this->selectTempDataFromDb('value_name', 'theCorrectName');
		$this->assertCount(1, $dbSelectNew);
		$this->assertEquals('theCorrectName', $dbSelectNew[0]['value_name']);
		$this->assertEquals('changed', $dbSelectNew[0]['value_status']);
		$idAfter = $dbSelectNew[0]['id'];
		$this->assertEquals($idBefore, $idAfter);
	}

	function testUpdateReplaceMultipleMatches() {
		//select before use pdoUtil()->update
		$dbSelect = $this->selectTempDataFromDb('value_name', 'aNewName');
		$this->assertCount(2, $dbSelect);
		$this->assertEquals('aNewName', $dbSelect[0]['value_name']);
		$this->assertEquals('initial', $dbSelect[0]['value_status']);
		$this->assertEquals('aNewName', $dbSelect[1]['value_name']);
		$this->assertEquals('new', $dbSelect[1]['value_status']);

		//if we specify matches we can select the correct one of the two selects
		TestEnv::pdoUtil()->update('n2n_test_tbl',
				['value_name' => 'aDifferentName'], ['value_name' => 'aNewName', 'value_status' => 'initial']);

		//select after use pdoUtil()->update
		$dbSelectNew = $this->selectTempDataFromDb('value_name', 'aNewName');
		$this->assertCount(1, $dbSelectNew);
		$this->assertEquals('aNewName', $dbSelectNew[0]['value_name']);
		$this->assertEquals('new', $dbSelectNew[0]['value_status']);
	}

	function testReplaceNullValue() {
		TestEnv::pdoUtil()->update('n2n_test_tbl',
				['value_name' => null], ['value_name' => 'aNewName']);
		$this->assertEquals(null, TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status'], ['value_name' => null])[0]['value_name']);
		TestEnv::pdoUtil()->update('n2n_test_tbl',
				['value_name' => 'aExNullName'], ['value_name' => null]);

		$this->assertEquals('aExNullName', TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status'], ['value_name' => 'aExNullName'])[0]['value_name']);
	}

	function testSelectValueMatchedByValue() {
		$pdoUtilSelect = TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status'], ['value_name' => 'aWrongName']);
		$this->assertEquals('aWrongName', $pdoUtilSelect[0]['value_name']);
		$this->assertEquals('new', $pdoUtilSelect[0]['value_status']);

		$this->assertEquals(['value_name' => 'aWrongName'],
				TestEnv::pdoUtil()->select('n2n_test_tbl', ['value_name'], ['value_name' => 'aWrongName'])[0]);

		//we get 4 Rows (2 of 6 Rows has not value_status = initial)
		$this->assertCount(4, TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status'], ['value_status' => 'initial']));

		//these rows have each (3) columns that we ask for
		$this->assertCount(3, TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status'], ['value_status' => 'initial'])[0]);


		$this->assertEquals($this->selectTempDataFromDb('value_status', 'initial'),
				TestEnv::pdoUtil()->select('n2n_test_tbl', ['id', 'value_name', 'value_status'], ['value_status' => 'initial']));
	}

	function testSelectNull() {
		$dbSelect = TestEnv::pdoUtil()->select('n2n_test_tbl',
				null);
		$this->assertCount(6, $dbSelect);
		$this->assertCount(3, $dbSelect[0]);
		$this->assertEquals('aGoodName', $dbSelect[0]['value_name']);
		$this->assertEquals('initial', $dbSelect[0]['value_status']);
		$this->assertNotNull($dbSelect[0]['id']);
	}

	function testSelectDefaultNull() {
		$dbSelect = TestEnv::pdoUtil()->select('n2n_test_tbl');
		$this->assertCount(6, $dbSelect);
		$this->assertCount(3, $dbSelect[0]);
		$this->assertEquals('aGoodName', $dbSelect[0]['value_name']);
		$this->assertEquals('initial', $dbSelect[0]['value_status']);
		$this->assertNotNull($dbSelect[0]['id']);
	}

	function testSelectValueMultipleMatches() {
		//check and count with single matches
		$dbSelect = TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status'], ['value_name' => 'aNewName']);
		$this->assertCount(2, $dbSelect);
		$this->assertEquals('aNewName', $dbSelect[0]['value_name']);
		$this->assertEquals('initial', $dbSelect[0]['value_status']);
		$this->assertEquals('aNewName', $dbSelect[1]['value_name']);
		$this->assertEquals('new', $dbSelect[1]['value_status']);

		//check and count after refine matches with second value
		$dbSelectSpecific = TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status'], ['value_name' => 'aNewName', 'value_status' => 'new']);
		$this->assertCount(1, $dbSelectSpecific);
		$this->assertEquals('aNewName', $dbSelectSpecific[0]['value_name']);
		$this->assertEquals('new', $dbSelectSpecific[0]['value_status']);
	}

	function testInsertValue() {
		//count before
		$this->assertCount(6, TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status']));
		//insert one entry and two additional entries each with 3 column/values
		TestEnv::pdoUtil()->insert('n2n_test_tbl',
				['id' => 99, 'value_name' => 'aInsertName', 'value_status' => 'insert'],
				[[100, 'anOtherInsertName', 'additionalInsert'], [101, 'yetAnOtherInsertName', 'additionalInsert']]);
		//count after and check entries
		$this->assertCount(9, TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status']));
		$this->assertCount(1, TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status'], ['value_status' => 'insert']));
		$this->assertCount(2, TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status'], ['value_status' => 'additionalInsert']));
	}

	function testInsertTestDefaultAndAutoValue() {
		//count before
		$this->assertCount(6, TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status']));
		//insert with only value_name, id is autoincrement, and value_status has an default value
		TestEnv::pdoUtil()->insert('n2n_test_tbl',
				['value_name' => 'aInsertName']);
		//count after
		$this->assertCount(7, TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status']));
		//check default value, and not empty autoincrement value
		$dbSelect = TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status'], ['value_name' => 'aInsertName']);
		$this->assertCount(1, $dbSelect);
		$this->assertEquals('initial', $dbSelect[0]['value_status']);
		$this->assertNotNull($dbSelect[0]['id']);
	}

	function testDeleteValue() {
		//count before
		$this->assertCount(6, TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status']));
		//delete single entry
		TestEnv::pdoUtil()->delete('n2n_test_tbl',
				['value_name' => 'aWrongName']);
		//count after delete
		$this->assertCount(5, TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status']));
		//delete multiple entries with same match
		TestEnv::pdoUtil()->delete('n2n_test_tbl',
				['value_status' => 'initial']);
		//count after delete
		$this->assertCount(1, TestEnv::pdoUtil()->select('n2n_test_tbl',
				['id', 'value_name', 'value_status']));
	}


	function testCount() {
		$this->assertEquals(6, TestEnv::pdoUtil()->count('n2n_test_tbl'));
	}

	function testCountValuesMatchedByValue() {
		//value that is unique
		$this->assertEquals(1, TestEnv::pdoUtil()->count('n2n_test_tbl', ['value_name' => 'aWrongName']));
		//value that is here twice
		$this->assertEquals(2, TestEnv::pdoUtil()->count('n2n_test_tbl', ['value_name' => 'aNewName']));
		//value refined by multiple where will give a single result
		$this->assertEquals(1, TestEnv::pdoUtil()->count('n2n_test_tbl', ['value_name' => 'aNewName', 'value_status' => 'new']));
		//other where criteria, we get 4 Rows (2 of 6 Rows has not value_status = initial)
		$this->assertEquals(4, TestEnv::pdoUtil()->count('n2n_test_tbl', ['value_status' => 'initial']));

		$this->assertEquals(count($this->selectTempDataFromDb('value_status', 'initial')),
				TestEnv::pdoUtil()->count('n2n_test_tbl', ['value_status' => 'initial']));
	}
}