<?php
/**
 * Admin Functions — Facade Module (Phase 6)
 * 
 * This file acts as a single entry point that loads all 
 * modular admin sub-modules. Existing code that does 
 * `require_once FUNC_PATH . 'admin.php'` will continue to work.
 *
 * Architecture: functions/admin/
 *   users.php        — User CRUD & verification
 *   reservations.php — Reservation management
 *   vehicles.php     — Vehicle management
 *   commissions.php  — Commission & billing (with double-pay protection)
 *   dashboard.php    — Dashboard KPIs (optimized queries)
 *   notifications.php— Admin notification dispatch
 *   audit.php        — Admin audit logging
 */

require_once __DIR__ . '/admin/utils.php';
require_once __DIR__ . '/admin/users.php';
require_once __DIR__ . '/admin/reservations.php';
require_once __DIR__ . '/admin/vehicles.php';
require_once __DIR__ . '/admin/commissions.php';
require_once __DIR__ . '/admin/dashboard.php';
require_once __DIR__ . '/admin/notifications.php';
require_once __DIR__ . '/admin/audit.php';
