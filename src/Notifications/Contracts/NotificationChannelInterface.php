<?php

namespace Fulgid\LogManagement\Notifications\Contracts;

interface NotificationChannelInterface
{
    /**
     * Send a notification through this channel.
     *
     * @param array $logData The log data to send
     * @return bool True if the notification was sent successfully, false otherwise
     */
    public function send(array $logData): bool;

    /**
     * Check if this notification channel is enabled.
     *
     * @return bool True if the channel is enabled, false otherwise
     */
    public function isEnabled(): bool;

    /**
     * Get the channel name.
     *
     * @return string The channel name
     */
    public function getName(): string;

    /**
     * Validate the channel configuration.
     *
     * @return bool True if the configuration is valid, false otherwise
     */
    public function validateConfiguration(): bool;

    /**
     * Get the channel configuration requirements.
     *
     * @return array Array of required configuration keys
     */
    public function getConfigurationRequirements(): array;

    /**
     * Test the channel connectivity.
     *
     * @return array Test results with success status and message
     */
    public function testConnection(): array;
}