<?php

namespace Fulgid\LogManagement\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void notify(string $message, string $level = 'error', array $context = [])
 * @method static void addNotificationChannel(string $name, \Fulgid\LogManagement\Notifications\Contracts\NotificationChannelInterface $channel)
 * @method static void removeNotificationChannel(string $name)
 * @method static array getNotificationChannels()
 * @method static void addFilter(callable $filter)
 * @method static void removeFilter(callable $filter)
 * @method static bool isEnabled()
 * @method static array getStats()
 */
class LogManagement extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'log-management';
    }
}