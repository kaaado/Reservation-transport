<?php
// Main Loader for Reservation sub-module (Phase 5 Refactored)

require_once __DIR__ . '/reservation/constants.php';
require_once __DIR__ . '/reservation/validation.php';
require_once __DIR__ . '/reservation/queries.php';
require_once __DIR__ . '/reservation/lifecycle.php';

// Maintain legacy function names for compatibility with views
// Most names already match from our refactoring. 
// acceptReservation replaced acceptRequest before, but we are keeping it unified. 
