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
use n2n\core\N2N;
use n2n\core\container\impl\AppN2nContext;
use n2n\core\cache\AppCache;
use n2n\core\container\Transaction;
use ReflectionClass;
use n2n\persistence\orm\EntityManager;

class TestEnv {

	private static ?AppN2nContext $n2nContext = null;

	private static array $additionalN2nContexts = [];

	static function replaceN2nContext(bool $keepTransactionContext = true): N2nContext {
		self::resetN2nContext();

		self::$n2nContext = N2N::forkN2nContext(keepTransactionContext: $keepTransactionContext);

		return self::getN2nContext();
	}

	static function getN2nContext(): AppN2nContext {
		return self::$n2nContext ?? self::$n2nContext = N2N::forkN2nContext(keepTransactionContext: true);
	}

	static function forkN2nContext(bool $keepTransactionContext = true): AppN2nContext {
		return self::$additionalN2nContexts[] = N2N::forkN2nContext(keepTransactionContext: $keepTransactionContext);
	}

	static function resetN2nContext(): void {
		while (null !== ($additionalN2nContext = array_pop(self::$additionalN2nContexts))) {
			if (!$additionalN2nContext->isFinalized()) {
				$additionalN2nContext->finalize();
			}
		}

		self::$n2nContext?->finalize();
		self::$n2nContext = null;
	}

	static function getAppCache(): AppCache {
		return N2N::getN2nContext()->getAppCache();
	}

	public static function container(?N2nContext $n2nContext = null): ContainerTestEnv {
		return new ContainerTestEnv($n2nContext ?? self::getN2nContext());
	}

	public static function http(?N2nContext $n2nContext = null): HttpTestEnv {
		return new HttpTestEnv($n2nContext ?? self::getN2nContext());
	}

	public static function orm(?N2nContext $n2nContext = null): OrmTestEnv {
		return new OrmTestEnv($n2nContext ?? self::getN2nContext());
	}

	public static function db(?N2nContext $n2nContext = null): DbTestEnv {
		return new DbTestEnv($n2nContext ?? self::getN2nContext());
	}

	public static function createTransaction(bool $readOnly = false, ?N2nContext $n2nContext = null): Transaction {
		return self::container($n2nContext)->tm()->createTransaction($readOnly);
	}


	/**
	 * @template T
	 * @param class-string<T>|ReflectionClass $id
	 * @return T|mixed
	 */
	public static function lookup(string|ReflectionClass $id, ?N2nContext $n2nContext = null): mixed {
		return self::container($n2nContext)->lookup($id);
	}

	/**
	 * @param string $id
	 * @param object $obj
	 * @param N2nContext|null $n2nContext
	 * @return void
	 */
	static function inject(string $id, object $obj, ?N2nContext $n2nContext = null): void {
		self::container($n2nContext)->inject($id, $obj);
	}


	public static function em(?string $persistenceUnitName = null, ?N2nContext $n2nContext = null): EntityManager {
		return self::orm($n2nContext)->em(false, $persistenceUnitName);
	}

	public static function tem(?string $persistenceUnitName = null, ?N2nContext $n2nContext = null): EntityManager {
		return self::orm($n2nContext)->em(true, $persistenceUnitName);
	}

	public static function emUtil(?string $persistenceUnitName = null, ?N2nContext $n2nContext = null): OrmTestEmUtil {
		return self::orm($n2nContext)->emUtil(false, $persistenceUnitName);
	}

	public static function temUtil(?string $persistenceUnitName = null, ?N2nContext $n2nContext = null): OrmTestEmUtil {
		return self::orm($n2nContext)->emUtil(true, $persistenceUnitName);
	}

	public static function pdoUtil(?string $persistenceUnitName = null, ?N2nContext $n2nContext = null): DbTestPdoUtil {
		return self::db($n2nContext)->pdoUtil($persistenceUnitName);
	}
}
