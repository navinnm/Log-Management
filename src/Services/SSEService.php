<?php

namespace App\Services;

class SSEService
{
    protected $clients;

    public function __construct()
    {
        $this->clients = [];
    }

    public function addClient($client)
    {
        $this->clients[] = $client;
    }

    public function removeClient($client)
    {
        $this->clients = array_filter($this->clients, function ($c) use ($client) {
            return $c !== $client;
        });
    }

    public function sendEvent($data)
    {
        foreach ($this->clients as $client) {
            // Here you would implement the logic to send the event data to the client
            // For example, using a response stream or similar mechanism
        }
    }
}