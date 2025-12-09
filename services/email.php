<?php

require_once __DIR__ . '/../config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';

/**
 * Send an email using PHPMailer
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body HTML email body
 * @param string|null $recipientName Recipient name (optional)
 * @return bool True on success, false on failure
 */
function send_email($to, $subject, $body, $recipientName = null) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = env('MAIL_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = env('MAIL_USERNAME');
        $mail->Password   = env('MAIL_PASSWORD');
        $mail->SMTPSecure = env('MAIL_ENCRYPTION', 'tls');
        $mail->Port       = (int)env('MAIL_PORT', 587);
        
        // Error reporting
        $mail->SMTPDebug  = 0; // Disable verbose debug output
        
        // Recipients
        $fromAddress = env('MAIL_FROM_ADDRESS', 'noreply@rentallanka.com');
        $fromName = env('MAIL_FROM_NAME', 'RentalLanka');
        
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($to, $recipientName ?? '');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Plain text version
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email send failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send a welcome email to a newly registered user
 * 
 * @param string $email User's email address
 * @param string $name User's name
 * @return bool True on success, false on failure
 */
function send_welcome_email($email, $name) {
    $subject = "Welcome to RentalLanka!";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #2D5016 0%, #4A7C2C 100%); color: white; padding: 30px 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; }
            .button { display: inline-block; padding: 12px 30px; background: #2D5016; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; border-radius: 0 0 10px 10px; }
            .highlight { background: #f0f7e6; padding: 15px; border-left: 4px solid #2D5016; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0; font-size: 28px;'>Welcome to RentalLanka!</h1>
            </div>
            <div class='content'>
                <h2 style='color: #2D5016;'>Hello " . htmlspecialchars($name) . "! 🎉</h2>
                <p>Thank you for registering with <strong>RentalLanka</strong>, Sri Lanka's premier platform for property, room, and vehicle rentals!</p>
                
                <div class='highlight'>
                    <p style='margin: 0;'><strong>Your registration was successful!</strong></p>
                </div>
                
                <p>You can now:</p>
                <ul>
                    <li>Browse thousands of properties, rooms, and vehicles</li>
                    <li>Save your favorite listings to your wishlist</li>
                    <li>Contact property owners directly</li>
                    <li>Manage your bookings with ease</li>
                </ul>
                
                <div style='text-align: center;'>
                    <a href='" . app_url() . "' class='button'>Start Exploring</a>
                </div>
                
                <p>To log in, simply use your registered mobile number and request an OTP on our login page.</p>
                
                <p>If you have any questions or need assistance, feel free to reach out to our support team.</p>
                
                <p style='margin-top: 30px;'>Best regards,<br><strong>The RentalLanka Team</strong></p>
            </div>
            <div class='footer'>
                <p style='margin: 0 0 10px 0;'>© " . date('Y') . " RentalLanka. All rights reserved.</p>
                <p style='margin: 0;'>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return send_email($email, $subject, $body, $name);
}

/**
 * Send a beautifully formatted email for package status updates
 * 
 * @param string $email
 * @param string $name
 * @param string $packageName
 * @param string $status 'approved' or 'rejected'
 * @return bool
 */
function send_package_status_email($email, $name, $packageName, $status) {
    
    $isApproved = ($status === 'approved');
    $subject = $isApproved ? "Package Request Approved - Rental Lanka" : "Package Request Update - Rental Lanka";
    
    $color = $isApproved ? "#2D5016" : "#dc3545"; // Green or Red
    $icon = $isApproved ? "✅" : "❌";
    $headline = $isApproved ? "You're All Set! 🎉" : "Action Required";
    
    $messageContent = "";
    if ($isApproved) {
        $messageContent = "
            <p>Your request for the <strong>" . htmlspecialchars($packageName) . "</strong> package has been <strong style='color: #2D5016;'>APPROVED</strong>.</p>
            <p>You can now start listing your properties, rooms, and vehicles immediately.</p>
            <div style='background: #f0f7e6; padding: 15px; border-left: 4px solid #2D5016; margin: 20px 0;'>
                <p style='margin: 0;'><strong>What's Next?</strong><br>Log in to your dashboard and click 'Add Property' to get started!</p>
            </div>
        ";
    } else {
        $messageContent = "
            <p>We regret to inform you that your request for the <strong>" . htmlspecialchars($packageName) . "</strong> package has been <strong style='color: #dc3545;'>REJECTED</strong>.</p>
            <p>This is usually due to an unclear payment slip or invalid transaction details.</p>
            <div style='background: #fdeaea; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>
                <p style='margin: 0;'><strong>What to do?</strong><br>Please contact our support team or try purchasing the package again with a clear proof of payment.</p>
            </div>
        ";
    }

    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #444; background-color: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; }
            .header { background: linear-gradient(135deg, {$color} 0%, #1e3a0f 100%); color: white; padding: 40px 20px; text-align: center; }
            .content { padding: 40px 30px; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #888; font-size: 13px; border-top: 1px solid #eee; }
            .btn { display: inline-block; padding: 12px 30px; background-color: {$color}; color: white !important; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
            h1 { margin: 0; font-size: 24px; font-weight: 700; }
            p { margin-bottom: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                 <div style='font-size: 40px; margin-bottom: 10px;'>{$icon}</div>
                <h1>{$headline}</h1>
            </div>
            <div class='content'>
                <h3 style='color: #333;'>Hello " . htmlspecialchars($name) . ",</h3>
                {$messageContent}
                
                <p>Thank you for choosing <strong>Rental Lanka</strong>.</p>
                
                <div style='text-align: center;'>
                    <a href='" . app_url('auth/login') . "' class='btn'>Go to Dashboard</a>
                </div>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Rental Lanka. All rights reserved.</p>
                <p>Need help? Contact us at support@rentallanka.com</p>
            </div>
        </div>
    </body>
    </html>
    ";

    return send_email($email, $subject, $body, $name);
}
