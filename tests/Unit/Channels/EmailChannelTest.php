<?php

use PHPUnit\Framework\TestCase;
use fulgid\log_management\src\Notifications\Channels\EmailChannel;
use fulgid\log_management\src\Notifications\LogNotifier;
use fulgid\log_management\src\Models\LogEntry;

class EmailChannelTest extends TestCase
{
    protected $emailChannel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailChannel = new EmailChannel();
    }

    public function testSendNotification()
    {
        $logEntry = new LogEntry(['message' => 'Test log entry']);
        $notifier = new LogNotifier();

        $result = $this->emailChannel->send($logEntry, $notifier);

        $this->assertTrue($result);
    }

    public function testInvalidEmail()
    {
        $logEntry = new LogEntry(['message' => 'Test log entry']);
        $notifier = new LogNotifier();
        $this->emailChannel->setEmail('invalid-email');

        $result = $this->emailChannel->send($logEntry, $notifier);

        $this->assertFalse($result);
    }
}