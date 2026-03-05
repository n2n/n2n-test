<?php

namespace n2n\test\case;

use PHPUnit\Framework\Assert;

/**
 * Trait for extending PHPUnit TestCases with additional assertions.
 *
 * Usage => Include this trait in your TestCase (add it inside the class, the use above will be added automatic) like:
 *
 * ```
 * class N2nTestCaseTraitTest extends TestCase {
 *     use N2nTestCaseTrait;
 * }
 * ```
 *
 * Then use the provided assertions in your tests, like any PHPUnit assert function:
 *
 * ```
 * $this->assertTypeSafeEquals($expected, $actual, 'Optional failure message');
 * ```
 *
 * ```
 * $this->assertArrayContainsSubset($expectedSubset, $actualArray, 'Optional failure message');
 * ```
 */
trait N2nTestCaseTrait {

	public static function assertTypeSafeEquals($expected, $actual, ?string $message = null): void {
		Assert::assertThat($actual, new IsTypeSafeEqual($expected), $message ?? '');
	}

	public static function assertArrayContainsSubset(array $expectedSubset, array $actual, ?string $message = null): void {
		Assert::assertThat($actual, new ArrayHasSubset($expectedSubset), $message ?? '');
	}
}
