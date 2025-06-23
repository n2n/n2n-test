<?php

namespace n2n\test;

use n2n\persistence\Pdo;
use n2n\spec\dbo\meta\data\impl\QueryColumn;
use n2n\spec\dbo\meta\data\impl\QueryPlaceMarker;
use n2n\persistence\meta\data\QueryComparator;
use n2n\spec\dbo\meta\data\impl\QueryTable;
use n2n\util\type\ArgUtils;
use n2n\util\type\TypeConstraints;
use n2n\spec\dbo\meta\data\impl\QueryFunction;
use n2n\spec\dbo\meta\data\impl\QueryConstant;
use n2n\spec\dbo\meta\data\ComparisonBuilder;
use n2n\spec\dbo\err\DboException;

class DbTestPdoUtil {
	public function __construct(private Pdo $pdo) {
	}

	/**
	 * @throws DboException
	 */
	public function update(string $tableName, array $setMap, array $whereMatches = []): static {
		$builder = $this->pdo->getMetaData()->createUpdateStatementBuilder();
		$builder->setTable($tableName);
		$executeReplacementValues = [];

		foreach ($setMap as $queryColumn => $value) {
			$builder->addColumn(new QueryColumn($queryColumn), new QueryPlaceMarker());
			$executeReplacementValues[] = $value;
		}

		array_push($executeReplacementValues, ...$this->where($builder->getWhereComparator(), $whereMatches));

		$stmt = $this->pdo->prepare($builder->toSqlString());
		$stmt->execute($executeReplacementValues);

		return $this;
	}

	/**
	 * @throws DboException
	 */
	public function select(string $tableName, ?array $selectColumnNames = null, array $whereMatches = []): array {
		ArgUtils::valArray($selectColumnNames, 'string', true);
		ArgUtils::valArray($whereMatches, ['scalar', 'null'], true);

		$builder = $this->pdo->getMetaData()->createSelectStatementBuilder();
		$builder->addFrom(new QueryTable($tableName));

		foreach ((array) $selectColumnNames as $queryColumn) {
			$builder->addSelectColumn(new QueryColumn($queryColumn));
		}

		$executeSelectValues = $this->where($builder->getWhereComparator(), $whereMatches);

		$stmt = $this->pdo->prepare($builder->toSqlString());
		$stmt->execute($executeSelectValues);

		return ($stmt->fetchAll(\PDO::FETCH_ASSOC));
	}

	/**
	 * @throws DboException
	 */
	public function insert(string $tableName, array $firstValuesMap, array $additionalValuesRows = []): static {
		ArgUtils::valArray($firstValuesMap, ['scalar', 'null']);
		ArgUtils::valType($additionalValuesRows, TypeConstraints::array(false,
				TypeConstraints::array(false, TypeConstraints::namedType('scalar', true))));

		$builder = $this->pdo->getMetaData()->createInsertStatementBuilder();
		$builder->setTable($tableName);
		$executeInsertValues = [];
		foreach ($firstValuesMap as $queryColumn => $value) {
			$builder->addColumn(new QueryColumn($queryColumn), new QueryPlaceMarker());
			$executeInsertValues[] = $value;
		}

		foreach ($additionalValuesRows as $row) {
			$insertValueGroup = $builder->createAdditionalValueGroup();
			foreach ($row as $value) {
				$insertValueGroup->addValue(new QueryPlaceMarker());
				$executeInsertValues[] = $value;
			}
		}

		$stmt = $this->pdo->prepare($builder->toSqlString());
		$stmt->execute($executeInsertValues);

		return $this;
	}

	/**
	 * @throws DboException
	 */
	public function delete(string $tableName, array $whereMatches = []): static {
		ArgUtils::valArray($whereMatches, ['scalar', 'null'], true);
		$builder = $this->pdo->getMetaData()->createDeleteStatementBuilder();
		$builder->setTable($tableName);
		$executeDeleteValues = $this->where($builder->getWhereComparator(), $whereMatches);

		$stmt = $this->pdo->prepare($builder->toSqlString());
		$stmt->execute($executeDeleteValues);

		return $this;
	}

	/**
	 * @throws DboException
	 */
	public function count(string $tableName, array $whereMatches = []): int {
		ArgUtils::valArray($whereMatches, ['scalar', 'null'], true);
		$builder = $this->pdo->getMetaData()->createSelectStatementBuilder();
		$builder->addSelectColumn(new QueryFunction(QueryFunction::COUNT, new QueryConstant(1)));
		$builder->addFrom(new QueryTable($tableName));
		$executeCountValues = $this->where($builder->getWhereComparator(), $whereMatches);

		$stmt = $this->pdo->prepare($builder->toSqlString());
		$stmt->execute($executeCountValues);
		$arr = $stmt->fetch(\PDO::FETCH_NUM);

		return current($arr);
	}

	private function where(ComparisonBuilder $comparisonBuilder, array $whereMatches): array {
		$placeholderValues = [];
		foreach ($whereMatches as $columnName => $value) {
			$comparisonBuilder->match(new QueryColumn($columnName),
					($value === null ? ComparisonBuilder::OPERATOR_IS : ComparisonBuilder::OPERATOR_EQUAL),
					new QueryPlaceMarker());
			$placeholderValues[] = $value;
		}
		return $placeholderValues;
	}
}