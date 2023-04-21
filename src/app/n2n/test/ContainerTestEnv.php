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
use n2n\core\container\TransactionManager;

class ContainerTestEnv {
	/**
	 * @var N2nContext
	 */
	private $n2nContext;
	
	/**
	 * @param N2nContext $n2nContext
	 */
	public function __construct(N2nContext $n2nContext) {
		$this->n2nContext = $n2nContext;
	}
	
	/**
	 * Alias for
	 * @see OrmTestEnv::getTransactionManager()
	 */
	public function tm() {
		return $this->getTransactionManager();
	}
	
	/**
	 * @return TransactionManager
	 */
	public function getTransactionManager() {
		return $this->n2nContext->lookup(TransactionManager::class);
	}
	
	/**
	 * @template T
	 * @param class-string<T>|\ReflectionClass $id
	 * @return T|mixed
	 */
	public function lookup(string|\ReflectionClass $id): mixed {
		return $this->n2nContext->lookup($id);
	}

	/**
	 * @param string|\ReflectionClass $id
	 * @return void
	 */
	public function inject(string $id, object $obj) {
		$this->n2nContext->putLookupInjection($id, $obj);
	}
}
