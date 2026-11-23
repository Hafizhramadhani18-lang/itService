<?php

namespace App\Services;

use App\Models\User;

class AdminNotificationService
{
    /**
     * Build dynamic, role-specific executive notifications for Koordinator IT (Admin).
     */
    public static function getForAdmin(User $adminUser, int $limit = 10, bool $dailyOnly = false): array
    {
        return NotificationCenterService::getForUser($adminUser, $limit, $dailyOnly);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public static function markAsRead(User $adminUser, string $notifId): void
    {
        NotificationCenterService::markAsRead($adminUser, $notifId);
    }

    /**
     * Tandai seluruh notifikasi admin sebagai sudah dibaca.
     */
    public static function markAllAsRead(User $adminUser): void
    {
        NotificationCenterService::markAllAsRead($adminUser);
    }
}
