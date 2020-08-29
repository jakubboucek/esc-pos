<?php

declare(strict_types=1);

namespace JakubBoucek\EscPos;

use JakubBoucek\EscPos\Connections\IConnection;

class Printer
{
    /** @var IConnection */
    private $driver;

    public function __construct(IConnection $driver)
    {
        $this->driver = $driver;
    }

    public function writeRaw(string $data): void
    {
        $this->driver->send($data);
    }

    public function writeReceipt(Receipt $data): void
    {
        $this->driver->send($data->compile());
    }
}
