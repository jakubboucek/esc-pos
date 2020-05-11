<?php

namespace JakubBoucek\EscPos\Connections;

interface IConnection
{
    public function open();

    public function send($data);

    public function close();
}
