<?php

namespace EscPos;

/**
 * Printer .
 *
 * @author     Jakub Bouček
 */
class Printer {

	/** @var IDriver */
	private $driver;

	/**
	 * @param  IDriver
	 */
	public function __construct(IConnection $driver) {
		$this->driver = $driver;
	}

	public function write($data) {
		$this->driver->send((string) $data);

	}
}