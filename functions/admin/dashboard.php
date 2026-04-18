<?php
/**
 * Admin Functions — Dashboard KPIs Module
 * Optimized: combines queries where possible.
 */

function getAdminDashboardStats($pdo) {
    $stats = [];

    // Combined user stats in one query
    $userStats = $pdo->query("
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN role = 'client' THEN 1 ELSE 0 END) as total_clients,
            SUM(CASE WHEN role = 'transporter' THEN 1 ELSE 0 END) as total_transporters,
            SUM(CASE WHEN id_is_verified = 0 AND id_card_url IS NOT NULL THEN 1 ELSE 0 END) as pending_verifications
        FROM users
    ")->fetch(PDO::FETCH_ASSOC);

    $stats['total_users'] = $userStats['total_users'];
    $stats['total_clients'] = $userStats['total_clients'];
    $stats['total_transporters'] = $userStats['total_transporters'];
    $stats['pending_verifications'] = $userStats['pending_verifications'];

    // Combined reservation stats in one query
    $resStats = $pdo->query("
        SELECT
            COUNT(*) as total_reservations,
            SUM(CASE WHEN status IN ('pending','accepted','in_progress','negotiation') THEN 1 ELSE 0 END) as active_reservations,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reservations,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN platform_commission ELSE 0 END), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN status = 'completed' AND is_commission_paid = 0 THEN platform_commission ELSE 0 END), 0) as unpaid_commissions
        FROM reservations
    ")->fetch(PDO::FETCH_ASSOC);

    $stats['total_reservations'] = $resStats['total_reservations'];
    $stats['active_reservations'] = $resStats['active_reservations'];
    $stats['completed_reservations'] = $resStats['completed_reservations'];
    $stats['total_revenue'] = $resStats['total_revenue'];
    $stats['unpaid_commissions'] = $resStats['unpaid_commissions'];

    $stats['total_vehicles'] = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();

    $stats['blocked_transporters'] = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT v.owner_id FROM reservations r 
            JOIN vehicles v ON r.vehicle_id = v.id 
            WHERE r.status = 'completed' AND r.is_commission_paid = 0 
            GROUP BY v.owner_id HAVING COUNT(*) >= 5
        ) as blocked
    ")->fetchColumn() ?: 0;

    return $stats;
}
