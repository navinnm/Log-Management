<?php

use PHPUnit\Framework\TestCase;
use App\Notifications\Channels\WebhookChannel;
use App\Models\LogEntry;

class WebhookChannelTest extends TestCase
{
    public function testSendNotification()
    {
        $webhookUrl = 'https://example.com/webhook';
        $channel = new WebhookChannel($webhookUrl);
        
        $logEntry = new LogEntry([
            'message' => 'Test log entry',
            'level' => 'info',
        ]);

        $response = $channel->send($logEntry);

        $this->assertTrue($response);
    }

    public function testInvalidWebhookUrl()
    {
        $channel = new WebhookChannel('invalid-url');

        $logEntry = new LogEntry([
            'message' => 'Test log entry',
            'level' => 'error',
        ]);

        $response = $channel->send($logEntry);

        $this->assertFalse($response);
    }
}