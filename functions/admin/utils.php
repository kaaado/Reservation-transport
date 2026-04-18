<?php
/**
 * Admin Functions — Utilities & Helpers
 */

/**
 * Translate internal status values to French
 */
function translateStatus($status) {
    $translations = [
        'active'      => 'Actif',
        'pending'     => 'En attente',
        'suspended'   => 'Suspendu',
        'cancelled'   => 'Annulé',
        'rejected'    => 'Refusé',
        'accepted'    => 'Accepté',
        'in_progress' => 'En cours',
        'completed'   => 'Terminé',
        'negotiation' => 'En négociation',
        'unread'      => 'Non lu',
        'read'        => 'Lu',
        'verified'    => 'Vérifié',
        'unverified'  => 'Non vérifié'
    ];

    return $translations[strtolower($status)] ?? ucfirst($status);
}

/**
 * Get status color badge class
 */
function getStatusBadgeClass($status) {
    return match(strtolower($status)) {
        'active', 'completed', 'verified', 'accepted' => 'status-completed',
        'pending', 'negotiation', 'unread' => 'status-pending',
        'suspended', 'cancelled', 'rejected', 'unverified' => 'status-cancelled',
        'in_progress' => 'status-in_progress',
        default => ''
    };
}
