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
