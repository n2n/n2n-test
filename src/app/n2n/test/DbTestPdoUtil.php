<?php

namespace n2n\test;

use n2n\persistence\Pdo;
use n2n\persistence\meta\data\QueryColumn;
use n2n\persistence\meta\data\QueryPlaceMarker;
use n2n\persistence\meta\data\QueryComparator;
use n2n\persistence\meta\data\QueryTable;
use n2n\util\type\ArgUtils;
use n2n\util\type\TypeConstraints;

class DbTestPdoUtil {
	public function __construct(private Pdo $pdo) {
	}

	public function update(string $tableName, array $setMap, array $whereMatches = []): static {
		$builder = $this->pdo->getMetaData()->createUpdateStatementBuilder();
		$builder->setTable($tableName);
		$executeReplacementValues = [];

		foreach ($setMap as $queryColumn => $value) {
			$builder->addColumn(new QueryColumn($queryColumn), new QueryPlaceMarker());
			$executeReplacementValues[] = $value;
		}

		foreach ($whereMatches as $columnName => $value) {
			$builder->getWhereComparator()->match(new QueryColumn($columnName),
					($value === null ? QueryComparator::OPERATOR_IS : QueryComparator::OPERATOR_EQUAL),
					new QueryPlaceMarker());
			$executeReplacementValues[] = $value;
		}

		$stmt = $this->pdo->prepare($builder->toSqlString());
		$stmt->execute($executeReplacementValues);

		return $this;
	}

	public function select(string $tableName, ?array $selectColumnNames = null, array $whereMatches = []): array {
		ArgUtils::valArray($selectColumnNames, 'string', true);
		ArgUtils::valArray($whereMatches, ['scalar', 'null'], true);

		$builder = $this->pdo->getMetaData()->createSelectStatementBuilder();
		$builder->addFrom(new QueryTable($tableName));
		$executeSelectValues = [];

		foreach ((array) $selectColumnNames as $queryColumn) {
			$builder->addSelectColumn(new QueryColumn($queryColumn));
		}

		foreach ($whereMatches as $columnName => $value) {
			$builder->getWhereComparator()->match(new QueryColumn($columnName),
					($value === null ? QueryComparator::OPERATOR_IS : QueryComparator::OPERATOR_EQUAL),
					new QueryPlaceMarker());
			$executeSelectValues[] = $value;
		}

		$stmt = $this->pdo->prepare($builder->toSqlString());
		$stmt->execute($executeSelectValues);

		return ($stmt->fetchAll(\PDO::FETCH_ASSOC));
	}

	public function insert(string $tableName, array $firstValuesMap, array $additionalValuesRows = []): static {
		ArgUtils::valArray($firstValuesMap, 'scalar');
		ArgUtils::valType($additionalValuesRows, TypeConstraints::array(false, TypeConstraints::array('scalar')));

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

	public function delete(string $tableName, array $whereMatches = []): static {
		$builder = $this->pdo->getMetaData()->createDeleteStatementBuilder();
		$builder->setTable($tableName);
		$executeDeleteValues = [];
		foreach ($whereMatches as $columnName => $value) {
			$builder->getWhereComparator()->match(new QueryColumn($columnName),
					($value === null ? QueryComparator::OPERATOR_IS : QueryComparator::OPERATOR_EQUAL),
					new QueryPlaceMarker());
			$executeDeleteValues[] = $value;
		}

		$stmt = $this->pdo->prepare($builder->toSqlString());
		$stmt->execute($executeDeleteValues);

		return $this;
	}
}