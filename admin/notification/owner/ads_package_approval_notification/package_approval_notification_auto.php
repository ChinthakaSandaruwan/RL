<?php

require_once __DIR__ . '/../../../../config/db.php';

/**
 * Send automated notification to owner about package approval status
 * 
 * @param int $userId
 * @param string $packageName
 * @param string $status 'approved' or 'rejected'
 * @return bool
 */
function notify_owner_package_status($userId, $packageName, $status) {
    $title = "";
    $message = "";

    if ($status === 'approved') {
        $title = "Package Approved";
        $message = "Your {$packageName} has been approved. You can now start listing your properties, rooms, and vehicles!";
    } elseif ($status === 'rejected') {
        $title = "Package Rejected";
        $message = "Your request for {$packageName} was rejected. Please check your email for details or contact support.";
    } else {
        return false;
    }

    // Use the helper from db.php
    return create_notification($userId, $title, $message, 1); // Type 1 = System
}
