<?php

namespace n2n\test\case;

use PHPUnit\Framework\Constraint\Constraint;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Exporter\Exporter;

class ArrayHasSubset extends Constraint {
	private ?string $failureReason = null;

	public function __construct(private array $expectedSubset) {
	}

	public function toString(): string {
		return 'contains the expected subset (strict compare)';
	}

	protected function matches(mixed $other): bool {
		return $this->compareSubset($this->expectedSubset, $other);
	}

	private function compareSubset(array $expected, mixed $actual, string $path = ''): bool {
		if (!is_array($actual)) {
			$this->failureReason =
					'Type mismatch at ' . ($path ?: '[root]') .
					' (expected array, got ' . gettype($actual) . ')';
			return false;
		}

		foreach ($expected as $key => $expectedValue) {
			$currentPath = $path === '' ? '[' . $key . ']' : $path . '[' . $key . ']';

			if (!array_key_exists($key, $actual)) {
				$this->failureReason = 'Missing key at ' . $currentPath;
				return false;
			}

			$actualValue = $actual[$key];

			if (is_array($expectedValue)) {
				if (!is_array($actualValue)) {
					$this->failureReason =
							'Type mismatch at ' . $currentPath .
							' (expected array, got ' . gettype($actualValue) . ')';
					return false;
				}

				if (!$this->compareSubset($expectedValue, $actualValue, $currentPath)) {
					return false;
				}

			} else {
				if ($actualValue !== $expectedValue) {
					$this->failureReason =
							'Value mismatch at ' . $currentPath .
							' (expected ' . var_export($expectedValue, true) .
							', got ' . var_export($actualValue, true) . ')';
					return false;
				}
			}
		}

		return true;
	}

	protected function additionalFailureDescription($other): string {
		return $this->failureReason ?? '';
	}

	protected function fail(mixed $other, $description, ?ComparisonFailure $comparisonFailure = null): never {
		$exporter = new Exporter();

		if ($this->failureReason !== null) {
			$comparisonFailure = new ComparisonFailure(
					$this->expectedSubset,
					$other,
					$exporter->export($this->expectedSubset),
					$exporter->export($other),
					false,
					$this->failureReason
			);
		}

		parent::fail($other, $description, $comparisonFailure);
	}
}