<?php

namespace n2n\test;

use n2n\persistence\orm\EntityManager;
use ReflectionClass;
use n2n\reflection\property\InaccessiblePropertyException;
use n2n\reflection\property\UnknownPropertyException;
use n2n\reflection\property\InvalidPropertyAccessMethodException;
use n2n\reflection\property\PropertyAccessException;
use n2n\persistence\orm\CorruptedDataException;

class OrmTestEmUtil {

	public function __construct(private EntityManager $em) {
	}

	public function count(string|ReflectionClass $class, array $matches = []): int {
		return $this->em->createSimpleCriteria($class, $matches)->clearSelect()->select('COUNT(1)')->toQuery()
				->fetchSingle();
	}

	public function delete(string|ReflectionClass $class, array $matches = []): void {
		$toDeletedItems = $this->em->createSimpleCriteria($class, $matches)->toQuery()->fetchArray();
		foreach ($toDeletedItems as $item) {
			$this->em->remove($item);
		}
	}

	/**
	 * @throws InaccessiblePropertyException
	 * @throws UnknownPropertyException
	 * @throws InvalidPropertyAccessMethodException
	 * @throws PropertyAccessException
	 * @throws CorruptedDataException
	 */
	public function update(string|ReflectionClass $class, array $setMap, array $whereMatches = []): void {
		$entityObjs = $this->em->createSimpleCriteria($class, $whereMatches)->toQuery()->fetchArray();

		$entityModelManager = $this->em->getEntityModelManager();
		$entityModel = $entityModelManager->getEntityModelByClass($class);

//		$analyzer = new PropertiesAnalyzer(is_string($class) ? ExUtils::try(fn () => new ReflectionClass($class)) : $class);

		foreach ($setMap as $propertyName => $value) {
//			$accessProxy = $analyzer->analyzeProperty($propertyName);
			$entityProperty = $entityModel->getEntityPropertyByName($propertyName);
			foreach ($entityObjs as $entityObj) {
//				$accessProxy->setValue($entityObj, $value);
				$entityProperty->writeValue($entityObj, $value);
			}
		}

		foreach ($entityObjs as $entityObj) {
			$this->em->persist($entityObj);
		}
	}
}