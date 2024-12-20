<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\test;


use n2n\core\container\N2nContext;
use n2n\persistence\ext\PdoPool;
use n2n\util\type\CastUtils;
use n2n\persistence\Pdo;

class DbTestEnv {
	/**
	 * @var N2nContext
	 */
	private N2nContext $n2nContext;
	
	/**
	 * @param N2nContext $n2nContext
	 */
	public function __construct(N2nContext $n2nContext) {
		$this->n2nContext = $n2nContext;
	}

	/**
	 * @param string|null $persistenceUnitName
	 * @return Pdo
	 */
	public function pdo(?string $persistenceUnitName = null): Pdo {
		$pdoPool = $this->n2nContext->lookup(PdoPool::class);
		CastUtils::assertTrue($pdoPool instanceof PdoPool);
		
		return $pdoPool->getPdo($persistenceUnitName);
	}

	/**
	 * Truncates all tables in the database of the passed persitence unit.
	 *
	 * @param string|null $persistenceUnitName if null the database of the default persistence unit will be used.
	 * @param array|null $metaEntityNames
	 * @return DbTestEnv
	 */
	public function truncate(?string $persistenceUnitName = null, ?array $metaEntityNames = null): static {
		$pdo = $this->pdo($persistenceUnitName);
		$metaData = $pdo->getMetaData();
		$db = $metaData->getDatabase();

		if ($metaEntityNames === null) {
			$metaEntities = $db->getMetaEntities();
		} else {
			$metaEntities = array_map(fn ($n) => $db->getMetaEntityByName($n), $metaEntityNames);
		}

		foreach ($metaEntities as $metaEntity) {
			$delStmtBuilder = $metaData->createDeleteStatementBuilder();
			$delStmtBuilder->setTable($metaEntity->getName());
			$pdo->exec($delStmtBuilder->toSqlString());
		}

		return $this;
	}

	public function pdoUtil(?string $persistenceUnitName = null): DbTestPdoUtil {
		return new DbTestPdoUtil(self::pdo($persistenceUnitName));
	}
}
