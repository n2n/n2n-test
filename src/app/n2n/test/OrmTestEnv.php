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
use n2n\persistence\orm\EntityManager;
use n2n\persistence\ext\EmPool;
use n2n\persistence\orm\EntityManagerFactory;

class OrmTestEnv {
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
	 * Alias for
	 * @see OrmTestEnv::getEntityManager()
	 */
	public function em(bool $transactional = false, string $persistenceUnitName = null): EntityManager {
		return $this->getEntityManager($transactional, $persistenceUnitName);
	}

	/**
	 * @param bool $transactional
	 * @param string|null $persistenceUnitName
	 * @return EntityManager
	 */
	public function getEntityManager(bool $transactional = false, string $persistenceUnitName = null): EntityManager {
		$emf = $this->getEntityManagerFactory($persistenceUnitName);

		return $transactional ? $emf->getTransactional() : $emf->getExtended();
	}

	/**
	 * Alias for
	 * @see self::getEntityManagerFactory()
	 */
	public function emf(string $persistenceUnitName = null): EntityManagerFactory {
		return $this->getEntityManagerFactory($persistenceUnitName);
	}

	/**
	 * @param string|null $persistenceUnitName
	 * @return EntityManagerFactory
	 */
	public function getEntityManagerFactory(string $persistenceUnitName = null): EntityManagerFactory {
		$pdoPool = $this->n2nContext->lookup(EmPool::class);
		CastUtils::assertTrue($pdoPool instanceof EmPool);

		return $pdoPool->getEntityManagerFactory($persistenceUnitName);
	}

	public function emUtil(bool $transactional = false, string $persistenceUnitName = null): OrmTestEmUtil {
		return new OrmTestEmUtil(self::getEntityManager($transactional, $persistenceUnitName));
	}
}
