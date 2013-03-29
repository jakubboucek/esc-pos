<?php

namespace EscPos;

class Printer {
	private $driver;

	public function __construct(IDriver $driver) {
		$this->driver = $driver;
	}

	public function newReceipt() {
		return new Receipt( $this->driver );
	}
}