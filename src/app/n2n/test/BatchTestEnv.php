<?php

namespace n2n\test;

use n2n\core\container\impl\AppN2nContext;
use n2n\core\ext\N2nBatch;
use n2n\util\ex\IllegalStateException;
use n2n\core\ext\BatchTriggerConfig;
use n2n\test\ex\TestConstraintFailedException;

class BatchTestEnv {
	function __construct(private AppN2nContext $n2nContext) {
	}

	private function n2nBatch(): N2nBatch {
		$batch = $this->n2nContext->getBatch();
		if ($batch !== null) {
			return $batch;
		}

		throw new IllegalStateException('N2N batch not installed.');
	}

	function triggerSingle(string $batchJobClassName, ?\DateTimeImmutable $dateTime = null,
			?\DateTimeImmutable $lastTriggeredDateTime = null): void {
		$this->n2nBatch()->trigger(new BatchTriggerConfig($dateTime ?? new \DateTimeImmutable(),
				$lastTriggeredDateTime, [$batchJobClassName], $this->n2nContext));
	}

	function dispatch(mixed $batchJob): void {
		$this->n2nBatch()->dispatch($batchJob);
	}

	/**
	 * @template T
	 * @param object $obj
	 * @param class-string<T> $expectedReturnTypeName
	 * @return T
	 */
	function dispatchAndReadSingleReturnObj(object $obj, string $expectedReturnTypeName): mixed {
		$results = $this->n2nBatch()->dispatch($obj);

		if (count($results) !== 1) {
			throw new TestConstraintFailedException('Dispatch of ' . get_class($obj)
					. ' returned multiple results: ' . count($results));
		}

		return $results[0]->readReturnObj($expectedReturnTypeName);
	}
}