<?php

namespace n2n\test;

use PHPUnit\Framework\TestCase;
use n2n\util\DateUtils;

class BatchTestEnvTest extends TestCase {
	function setUp(): void {
		TestEnv::replaceN2nContext();
	}

	function testTriggerSingle(): void {
		TestEnv::batch()->triggerSingle(BatchJobMock::class);

		$this->assertSame(
				['_onTrigger', '_onNewHour'],
				TestEnv::getN2nContext()->lookup(BatchJobMock::class)->calledMethodNames);
	}

	/**
	 * @throws \DateInvalidOperationException
	 */
	function testTriggerSingleWithDateTimes(): void {
		$now = new \DateTimeImmutable('1985-09-07 12:12:12');

		TestEnv::batch()->triggerSingle(BatchJobMock::class, $now,
				$now->sub(DateUtils::dateInterval(i: 1)));

		$this->assertSame(
				['_onTrigger'],
				TestEnv::getN2nContext()->lookup(BatchJobMock::class)->calledMethodNames);
	}

}