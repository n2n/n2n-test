<?php

namespace n2n\test\bo;

use n2n\reflection\ObjectAdapter;

class N2nTestDummyObject extends ObjectAdapter {

	private int $id;
	private string $dummyString;

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getDummyString(): string {
		return $this->dummyString;
	}

	/**
	 * @param string $dummyString
	 */
	public function setDummyString(string $dummyString): void {
		$this->dummyString = $dummyString;
	}


}