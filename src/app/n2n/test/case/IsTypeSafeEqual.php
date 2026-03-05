<?php

namespace n2n\test\case;

use PHPUnit\Framework\Constraint\Constraint;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Exporter\Exporter;

/**
 * Equivalent to a strict `===` check for values instead of '==', but ignore object reference
 * nearly as strict as assertSame, but allows that object don't need same reference, like assertEquals,
 * still type safe values are needed for sub-object properties
 */
class IsTypeSafeEqual extends Constraint {

	public function __construct(private $expected) {
	}

	public function toString(): string {
		return 'is strictly equal to expected value (type + value, no object identity)';
	}

	protected function matches($other): bool {
		return $this->compare($this->expected, $other);
	}

	private function compare($a, $b): bool {
		if (is_object($a) && is_object($b)) {
			return get_class($a) === get_class($b) && $this->compare((array) $a, (array) $b);
		}

		if (is_array($a) && is_array($b)) {
			if (array_keys($a) !== array_keys($b)) {
				return false;
			}
			foreach ($a as $key => $value) {
				if (!$this->compare($value, $b[$key])) {
					return false;
				}
			}
			return true;
		}

		return $a === $b;
	}

	protected function fail(mixed $other, $description, ?ComparisonFailure $comparisonFailure = null): never {
		$exporter = new Exporter();

		$comparisonFailure = new ComparisonFailure(
				$this->expected,
				$other,
				$exporter->export($this->expected),
				$exporter->export($other),
				false,
				'Failed asserting that two values are identical ' .
				'(strict compare, object references are shown as error ' .
				'for debugging reason, but ignored on check itself).'
		);

		parent::fail($other, $description, $comparisonFailure);
	}
}

