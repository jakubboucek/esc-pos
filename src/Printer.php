<?php

namespace JakubBoucek\EscPos;

class Printer
{
    /** @var IConnection */
    private $driver;

    /**
     * @param IConnection
     */
    public function __construct(IConnection $driver)
    {
        $this->driver = $driver;
    }

    public function write($data)
    {
        $this->driver->send((string)$data);
    }
}
