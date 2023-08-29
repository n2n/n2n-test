<?php

namespace n2n\test;

use n2n\persistence\orm\EntityManager;
use ReflectionClass;

class OrmTestEmUtil {
	private EntityManager $em;

	public function __construct(EntityManager $em) {
		$this->em = $em;
	}

	public function count(string|ReflectionClass $class, array $matches = []): int {
		return $this->em->createSimpleCriteria($class, $matches)->clearSelect()->select('COUNT(1)')->toQuery()
				->fetchSingle();
	}
}