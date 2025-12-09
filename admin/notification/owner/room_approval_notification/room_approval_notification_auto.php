<?php

require_once __DIR__ . '/../../../../config/db.php';

/**
 * Send automated notification to owner about room approval status
 * 
 * @param int $userId
 * @param string $roomTitle
 * @param string $status 'approved' or 'rejected'
 * @return bool
 */
function notify_owner_room_status($userId, $roomTitle, $status) {
    $title = "";
    $message = "";

    if ($status === 'approved') {
        $title = "Room Approved";
        $message = "Your room '{$roomTitle}' is now live and visible to tenants!";
    } elseif ($status === 'rejected') {
        $title = "Room Rejected";
        $message = "Your room '{$roomTitle}' was rejected. Please review our guidelines and update your listing.";
    } else {
        return false;
    }

    return create_notification($userId, $title, $message, 1); 
}
