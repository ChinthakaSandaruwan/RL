<?php

require_once __DIR__ . '/../../../../config/db.php';

/**
 * Send automated notification to owner about property approval status
 * 
 * @param int $userId
 * @param string $propertyTitle
 * @param string $status 'approved' or 'rejected'
 * @return bool
 */
function notify_owner_property_status($userId, $propertyTitle, $status) {
    $title = "";
    $message = "";

    if ($status === 'approved') {
        $title = "Property Approved";
        $message = "Your property '{$propertyTitle}' is now live and visible to tenants!";
    } elseif ($status === 'rejected') {
        $title = "Property Rejected";
        $message = "Your property '{$propertyTitle}' was rejected. Please review our guidelines and update your listing.";
    } else {
        return false;
    }

    // Use the helper from db.php
    // We can pass property_id if we have it, but here we just send a generic message.
    // If we want to link it, we'd need property_id argument. For now matching the previous simple implementation.
    return create_notification($userId, $title, $message, 1); 
}
