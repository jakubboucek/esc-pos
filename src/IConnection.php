<?php
/**
 * Copyright (c) 2015 Jakub Bouček (https://jakub-boucek.cz)
 * License MIT, https://github.com/jakubboucek/esc-pos
 */

namespace EscPos;

interface IConnection {

	public function __construct($host);

	public function open();

	public function send($data);

	public function close();
}