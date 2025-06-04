<?php

namespace Fulgid\LogManagement\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

class LogNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $logData;
    protected string $notificationLevel;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $logData, string $level = 'error')
    {
        $this->logData = $logData;
        $this->notificationLevel = $level;
        $this->queue = config('log-management.notifications.queue', 'default');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = [];

        if (config('log-management.notifications.channels.mail.enabled', false)) {
            $channels[] = 'mail';
        }

        if (config('log-management.notifications.channels.slack.enabled', false)) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = $this->getEmailSubject();
        $level = strtoupper($this->logData['level']);
        $message = $this->logData['message'];
        $timestamp = $this->logData['timestamp'];
        $environment = $this->logData['environment'];

        return (new MailMessage)
            ->subject($subject)
            ->level($this->getMailLevel())
            ->greeting("Log Alert: {$level}")
            ->line("A {$level} level log has been recorded in your application.")
            ->line("**Message:** {$message}")
            ->line("**Environment:** {$environment}")
            ->line("**Timestamp:** {$timestamp}")
            ->when(!empty($this->logData['url']), function ($mail) {
                return $mail->line("**URL:** {$this->logData['url']}");
            })
            ->when(!empty($this->logData['context']), function ($mail) {
                return $mail->line("**Context:** " . json_encode($this->logData['context'], JSON_PRETTY_PRINT));
            })
            ->action('View Application', url('/'))
            ->line('Please investigate this issue as soon as possible.')
            ->view('log-management::emails.log-notification', [
                'logData' => $this->logData,
                'level' => $level,
            ]);
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack($notifiable): SlackMessage
    {
        $level = strtoupper($this->logData['level']);
        $message = $this->logData['message'];
        $environment = $this->logData['environment'];
        $timestamp = $this->logData['timestamp'];

        $slackMessage = (new SlackMessage)
            ->to(config('log-management.notifications.channels.slack.channel', '#alerts'))
            ->content("🚨 Log Alert: {$level} in {$environment}")
            ->attachment(function ($attachment) use ($level, $message, $environment, $timestamp) {
                $attachment->title("Log Details")
                    ->color($this->getSlackColor())
                    ->fields([
                        'Level' => $level,
                        'Environment' => $environment,
                        'Timestamp' => $timestamp,
                        'Message' => strlen($message) > 200 ? substr($message, 0, 200) . '...' : $message,
                    ]);

                if (!empty($this->logData['url'])) {
                    $attachment->field('URL', $this->logData['url']);
                }

                if (!empty($this->logData['context'])) {
                    $contextStr = json_encode($this->logData['context'], JSON_PRETTY_PRINT);
                    if (strlen($contextStr) > 500) {
                        $contextStr = substr($contextStr, 0, 500) . '...';
                    }
                    $attachment->field('Context', "```{$contextStr}```");
                }
            });

        return $slackMessage;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'log_notification',
            'level' => $this->logData['level'],
            'message' => $this->logData['message'],
            'timestamp' => $this->logData['timestamp'],
            'environment' => $this->logData['environment'],
            'context' => $this->logData['context'] ?? [],
        ];
    }

    /**
     * Get the email subject.
     */
    protected function getEmailSubject(): string
    {
        $prefix = config('log-management.notifications.channels.mail.subject_prefix', '[LOG ALERT]');
        $level = strtoupper($this->logData['level']);
        $environment = strtoupper($this->logData['environment']);
        
        return "{$prefix} {$level} in {$environment}";
    }

    /**
     * Get the mail level for styling.
     */
    protected function getMailLevel(): string
    {
        $level = strtolower($this->logData['level']);
        
        return match ($level) {
            'emergency', 'alert', 'critical' => 'error',
            'error' => 'error',
            'warning' => 'warning',
            'notice', 'info' => 'info',
            default => 'info',
        };
    }

    /**
     * Get the Slack color based on log level.
     */
    protected function getSlackColor(): string
    {
        $level = strtolower($this->logData['level']);
        
        return match ($level) {
            'emergency', 'alert', 'critical', 'error' => 'danger',
            'warning' => 'warning',
            'notice', 'info' => 'good',
            default => '#439FE0',
        };
    }

    /**
     * Get the notification tags for queue monitoring.
     */
    public function tags(): array
    {
        return [
            'log-notification',
            'level:' . strtolower($this->logData['level']),
            'environment:' . strtolower($this->logData['environment']),
        ];
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }

    /**
     * Get the number of times the job may be attempted.
     */
    public function tries(): int
    {
        return config('log-management.notifications.retry_attempts', 3);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Log the failure without creating a notification loop
        \Illuminate\Support\Facades\Log::channel('single')->error(
            'Failed to send log notification: ' . $exception->getMessage(),
            [
                'log_data' => $this->logData,
                'exception' => $exception->getTraceAsString(),
            ]
        );
    }
}