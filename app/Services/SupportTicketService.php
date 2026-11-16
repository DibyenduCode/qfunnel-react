<?php

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use RuntimeException;

class SupportTicketService
{
    public const DEFAULT_CHANNELS = [
        'email' => 'support@filterfunnel.in',
        'phone' => '+1 (800) 555-0199',
        'whatsapp' => '+1 (800) 555-0199',
        'hours' => 'Monday – Friday, 8:00 AM – 8:00 PM EST',
        'description' => 'Direct account manager support for Agency Scale plans',
    ];

    /**
     * Get direct support contact channels
     */
    public static function getSupportChannels(): array
    {
        $saved = PlatformSettingService::get('support_channels', self::DEFAULT_CHANNELS);
        if (!is_array($saved)) {
            return self::DEFAULT_CHANNELS;
        }

        return array_merge(self::DEFAULT_CHANNELS, $saved);
    }

    /**
     * Save direct support contact channels
     */
    public static function saveSupportChannels(array $data): array
    {
        $current = self::getSupportChannels();

        $updated = [
            'email' => trim($data['email'] ?? $current['email']),
            'phone' => trim($data['phone'] ?? $current['phone']),
            'whatsapp' => trim($data['whatsapp'] ?? $current['whatsapp']),
            'hours' => trim($data['hours'] ?? $current['hours']),
            'description' => trim($data['description'] ?? $current['description']),
        ];

        if (empty($updated['email'])) {
            $updated['email'] = self::DEFAULT_CHANNELS['email'];
        }

        PlatformSettingService::set('support_channels', $updated);
        return $updated;
    }

    /**
     * Create a new support ticket inquiry
     */
    public static function createTicket(array $data, ?string $ip = null): array
    {
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $category = trim($data['category'] ?? 'builder');
        $subject = trim($data['subject'] ?? '');
        $message = trim($data['message'] ?? '');

        if (empty($name)) {
            throw new InvalidArgumentException("Full name is required.");
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("A valid email address is required.");
        }
        if (empty($subject)) {
            throw new InvalidArgumentException("Subject is required.");
        }
        if (empty($message)) {
            throw new InvalidArgumentException("Please provide a detailed description of your issue.");
        }

        // Generate unique ticket identifier
        $ticketNum = 'QF-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));

        Database::execute(
            "INSERT INTO support_tickets (ticket_num, name, email, phone, category, subject, message, status, ip_address, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'open', ?, NOW(), NOW())",
            [$ticketNum, $name, $email, $phone ?: null, $category, $subject, $message, $ip]
        );

        $ticketId = (int)Database::lastInsertId();

        return [
            'id' => $ticketId,
            'ticket_num' => $ticketNum,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'category' => $category,
            'subject' => $subject,
            'message' => $message,
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get all tickets with optional filtering
     */
    public static function getAllTickets(?string $status = null, ?string $search = null): array
    {
        $sql = "SELECT * FROM support_tickets WHERE 1=1";
        $params = [];

        if (!empty($status) && in_array($status, ['open', 'in_progress', 'resolved'])) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $like = '%' . trim($search) . '%';
            $sql .= " AND (ticket_num LIKE ? OR name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY id DESC";

        return Database::query($sql, $params);
    }

    /**
     * Get ticket by ID
     */
    public static function getTicketById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM support_tickets WHERE id = ? LIMIT 1", [$id]);
    }

    /**
     * Update ticket status and admin notes
     */
    public static function updateTicketStatus(int $id, string $status, ?string $notes = null): bool
    {
        if (!in_array($status, ['open', 'in_progress', 'resolved'])) {
            throw new InvalidArgumentException("Invalid status '{$status}'.");
        }

        $ticket = self::getTicketById($id);
        if (!$ticket) {
            throw new RuntimeException("Ticket not found.");
        }

        $adminNotes = $notes !== null ? trim($notes) : $ticket['admin_notes'];

        Database::execute(
            "UPDATE support_tickets SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?",
            [$status, $adminNotes, $id]
        );

        return true;
    }

    /**
     * Delete a support ticket
     */
    public static function deleteTicket(int $id): bool
    {
        Database::execute("DELETE FROM support_tickets WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Get ticket telemetry counters
     */
    public static function getTicketStats(): array
    {
        $rows = Database::query(
            "SELECT status, COUNT(*) as count FROM support_tickets GROUP BY status"
        );

        $stats = [
            'total' => 0,
            'open' => 0,
            'in_progress' => 0,
            'resolved' => 0,
        ];

        foreach ($rows as $r) {
            $cnt = (int)$r['count'];
            $stats['total'] += $cnt;
            if (isset($stats[$r['status']])) {
                $stats[$r['status']] = $cnt;
            }
        }

        return $stats;
    }
}
