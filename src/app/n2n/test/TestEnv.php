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
use n2n\util\cache\impl\EphemeralCacheStore;
use n2n\context\config\SimpleLookupSession;

class TestEnv {

	private static ?N2nContext $n2ncontext = null;

	/**
	 * @param N2nContext|null $n2NContext
	 * @return N2nContext
	 */
	static function replaceN2nContext(N2nContext $n2NContext = null): N2nContext {
		return self::$n2ncontext = $n2NContext ?? self::copyN2nContext();
	}

	/**
	 * @return N2nContext
	 */
	static function copyN2nContext(): N2nContext {
		return AppN2nContext::createCopy(N2N::getN2nContext(), new SimpleLookupSession(), new EphemeralCacheStore());
	}

	/**
	 * @return void
	 */
	static function resetN2nContext() {
		self::$n2ncontext = null;
	}

	/**
	 * @return N2nContext
	 */
	static function getN2nContext(): N2nContext {
		return self::$n2ncontext ?? N2N::getN2nContext();
	}

	/**
	 * @param N2nContext $n2nContext
	 * @return \n2n\test\ContainerTestEnv
	 */
	public static function container(N2nContext $n2nContext = null) {
		return new ContainerTestEnv($n2nContext ?? self::getN2nContext());
	}
	
	/**
	 * @param N2nContext $n2nContext
	 * @return \n2n\test\HttpTestEnv
	 */
	public static function http(N2nContext $n2nContext = null) {
		return new HttpTestEnv($n2nContext ?? self::getN2nContext());
	}
	
	/**
	 * @param N2nContext $n2nContext
	 * @return \n2n\test\OrmTestEnv
	 */
	public static function orm(N2nContext $n2nContext = null) {
		return new OrmTestEnv($n2nContext ?? self::getN2nContext());
	}
	
	/**
	 * @param N2nContext $n2nContext
	 * @return DbTestEnv
	 */
	public static function db(N2nContext $n2nContext = null) {
		return new DbTestEnv($n2nContext ?? self::getN2nContext());
	}
	
	/**
	 * @param bool $readOnly
	 * @param N2nContext $n2nContext
	 * @return \n2n\core\container\Transaction
	 */
	public static function createTransaction(bool $readOnly = false, N2nContext $n2nContext = null) {
		return self::container($n2nContext)->tm()->createTransaction($readOnly);
	}
	
	/**
	 * @param string|\ReflectionClass $id
	 * @param N2nContext $n2nContext
	 * @return mixed
	 */
	public static function lookup($id, N2nContext $n2nContext = null) {
		return self::container($n2nContext)->lookup($id);
	}

	/**
	 * @param string $id
	 * @param object $obj
	 * @return void
	 */
	static function inject(string $id, object $obj, N2nContext $n2nContext = null) {
		return self::container($n2nContext)->inject($id, $obj);
	}

	/**
	 * @param string $persistenceUnitName
	 * @param N2nContext $n2nContext
	 * @return \n2n\persistence\orm\EntityManager
	 */
	public static function em(string $persistenceUnitName = null, N2nContext $n2nContext = null) {
		return self::orm($n2nContext)->em(false, $persistenceUnitName);
	}
	
	/**
	 * @param string $persistenceUnitName
	 * @param N2nContext $n2nContext
	 * @return \n2n\persistence\orm\EntityManager
	 */
	public static function tem(string $persistenceUnitName = null, N2nContext $n2nContext = null) {
		return self::orm($n2nContext)->em(true, $persistenceUnitName);
	}
}
