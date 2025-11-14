<?php

namespace n2n\test;

use n2n\core\container\impl\AppN2nContext;
use n2n\core\ext\N2nBatch;
use n2n\util\ex\IllegalStateException;
use n2n\core\ext\BatchTriggerConfig;

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
}