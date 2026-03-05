<?php

namespace n2n\test\bo;

use n2n\reflection\ObjectAdapter;

class N2nTestMassiveDummyObject extends ObjectAdapter implements \JsonSerializable {

	private int $id;
	private string $dummyString;
	private \DateTime $dateTime;
	private int $int = 42;
	private float $float = 3.14159265;

	public function __construct() {
		$this->dateTime = new \DateTime('now');
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	function setId(int $id): void {
		$this->id = $id;
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

	public function getDateTime(): \DateTime {
		return $this->dateTime;
	}

	public function setDateTime(\DateTime $dateTime): void {
		$this->dateTime = $dateTime;
	}

	public function getInt(): int {
		return $this->int;
	}

	public function setInt(int $int): void {
		$this->int = $int;
	}

	public function getFloat(): float {
		return $this->float;
	}

	public function setFloat(float $float): void {
		$this->float = $float;
	}


	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'dummyString' => $this->dummyString,
			'int' => $this->int,
			'float' => $this->float,
			'dateTime' => $this->dateTime->format('Y-m-d H:i:s'),
		];
	}
}