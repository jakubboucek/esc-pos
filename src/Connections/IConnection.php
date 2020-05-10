<?php

namespace JakubBoucek\EscPos\Connections;

interface IConnection
{
    public function __construct($host);

    public function open();

    public function send($data);

    public function close();
}
