<?php

require_once __DIR__ . '/../../../../config/db.php';

/**
 * Send automated notification to owner about vehicle approval status
 * 
 * @param int $userId
 * @param string $vehicleTitle
 * @param string $status 'approved' or 'rejected'
 * @return bool
 */
function notify_owner_vehicle_status($userId, $vehicleTitle, $status) {
    $title = "";
    $message = "";

    if ($status === 'approved') {
        $title = "Vehicle Approved";
        $message = "Your vehicle '{$vehicleTitle}' is now live and visible to renters!";
    } elseif ($status === 'rejected') {
        $title = "Vehicle Rejected";
        $message = "Your vehicle '{$vehicleTitle}' was rejected. Please review our guidelines and update your listing.";
    } else {
        return false;
    }

    return create_notification($userId, $title, $message, 1); 
}
