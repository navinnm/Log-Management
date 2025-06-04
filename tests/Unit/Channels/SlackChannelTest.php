<?php

use PHPUnit\Framework\TestCase;
use App\Notifications\Channels\SlackChannel;
use App\Models\LogEntry;

class SlackChannelTest extends TestCase
{
    protected $slackChannel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->slackChannel = new SlackChannel();
    }

    public function testSendNotification()
    {
        $logEntry = new LogEntry([
            'message' => 'Test log entry',
            'level' => 'info',
        ]);

        $result = $this->slackChannel->send($logEntry);

        $this->assertTrue($result);
    }

    public function testInvalidSlackWebhookUrl()
    {
        $this->slackChannel->setWebhookUrl('invalid-url');

        $logEntry = new LogEntry([
            'message' => 'Test log entry with invalid URL',
            'level' => 'error',
        ]);

        $result = $this->slackChannel->send($logEntry);

        $this->assertFalse($result);
    }
}