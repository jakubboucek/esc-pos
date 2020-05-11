<?php

declare(strict_types=1);

namespace JakubBoucek\EscPos\Connections;

class Network implements IConnection
{
    /** @var string */
    private $host;
    /** @var int */
    private $port;
    /** @var resource|null */
    private $socket;

    public function __construct(string $host, int $port = 9100)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function open(): void
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if (!$socket) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            socket_close($socket);
            throw new ConnectionException("Could not create socket: [$errorcode] $errormsg");
        }

        //set tiemout to 3sec
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 3, 'usec' => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 3, 'usec' => 0]);

        // connect to server
        $result = socket_connect($socket, $this->host, $this->port);
        if (!$result) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            socket_close($socket);
            throw new ConnectionException("Could not connect to server: [$errorcode] $errormsg");
        }
        $this->socket = $socket;
    }

    public function send(string $message): void
    {
        if (!$this->isOpened()) {
            $this->open();
        }

        $socket_wrt = socket_write($this->socket, $message, strlen($message));
        if (!$socket_wrt) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            socket_close($this->socket);
            throw new SendException("Could not send data to server: [$errorcode] $errormsg\n");
        }
    }

    public function close(): void
    {
        if ($this->isOpened()) {
            socket_close($this->socket);
        }
        $this->socket = null;
    }

    public function isOpened(): bool
    {
        return (bool)$this->socket;
    }
}
