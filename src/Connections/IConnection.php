<?php

declare(strict_types=1);

namespace JakubBoucek\EscPos\Connections;

interface IConnection
{
    public function open(): void;

    public function send(string $data): void;

    public function close(): void;
}
