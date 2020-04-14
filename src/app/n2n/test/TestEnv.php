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

class TestEnv {
	
	/**
	 * @param N2nContext $n2nContext
	 * @return \n2n\test\ContainerTestEnv
	 */
	public static function container(N2nContext $n2nContext = null) {
		return new ContainerTestEnv($n2nContext ?? N2N::getN2nContext());
	}
	
	/**
	 * @param N2nContext $n2nContext
	 * @return \n2n\test\ContainerTestEnv
	 */
	public static function http(N2nContext $n2nContext = null) {
		return new HttpTestEnv($n2nContext ?? N2N::getN2nContext());
	}
	
	/**
	 * @param N2nContext $n2nContext
	 * @return \n2n\test\OrmTestEnv
	 */
	public static function orm(N2nContext $n2nContext = null) {
		return new OrmTestEnv($n2nContext ?? N2N::getN2nContext());
	}
	
	/**
	 * @param N2nContext $n2nContext
	 * @return DbTestEnv
	 */
	public static function db(N2nContext $n2nContext = null) {
		return new DbTestEnv($n2nContext ?? N2N::getN2nContext());
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
	 * @return object
	 */
	public static function lookup($id, N2nContext $n2nContext = null) {
		return self::container()->lookup($id);
	}
	
	/**
	 * @param string $persistenceUnitName
	 * @param N2nContext $n2nContext
	 * @return \n2n\persistence\orm\EntityManager
	 */
	public static function em(string $persistenceUnitName = null, N2nContext $n2nContext = null) {
		return self::orm()->em(false, $persistenceUnitName);
	}
	
	/**
	 * @param string $persistenceUnitName
	 * @param N2nContext $n2nContext
	 * @return \n2n\persistence\orm\EntityManager
	 */
	public static function tem(string $persistenceUnitName = null, N2nContext $n2nContext = null) {
		return self::orm()->em(true, $persistenceUnitName);
	}
}
