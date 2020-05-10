<?php

namespace JakubBoucek\EscPos\Connections;

use JakubBoucek\EscPos\IConnection;

class Network implements IConnection
{
    private $host;
    private $port;
    private $socket;

    public function __construct($host, $port = 9100)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function open()
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

    public function send($message)
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

    public function close()
    {
        if ($this->isOpened()) {
            socket_close($this->socket);
        }
        $this->socket = null;
    }

    public function isOpened()
    {
        return (bool)$this->socket;
    }
}
