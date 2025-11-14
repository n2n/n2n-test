<?php

namespace n2n\test;

use n2n\context\attribute\ThreadScoped;

#[ThreadScoped]
class BatchJobMock {

	public array $calledMethodNames = [];

	function __construct() {
	}

	function _onTrigger(): void {
		$this->calledMethodNames[] = '_onTrigger';
	}

	function _onNewHour(): void {
		$this->calledMethodNames[] = '_onNewHour';
	}

}