<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';
require 'vendor/phpmailer/phpmailer/src/Exception.php';

function sendResetEmail($to_email, $to_name, $reset_link) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your.actual.gmail@gmail.com';  // Your Gmail address
        $mail->Password = '1234 5678 9012 3456';         // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Enable debug output
        $mail->SMTPDebug = 2; // Remove this in production

        // Recipients
        $mail->setFrom('your.actual.gmail@gmail.com', 'CONNECTIONZIM');
        $mail->addAddress($to_email, $to_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2 style="color: #333;">Password Reset Request</h2>
                <p>Hello ' . $to_name . ',</p>
                <p>We received a request to reset your password. Click the button below to reset it:</p>
                <p style="text-align: center;">
                    <a href="' . $reset_link . '" 
                       style="background-color: #007bff; color: white; padding: 12px 24px; 
                              text-decoration: none; border-radius: 4px; display: inline-block;">
                        Reset Password
                    </a>
                </p>
                <p>If you did not request this password reset, please ignore this email.</p>
                <p>This link will expire in 1 hour for security reasons.</p>
                <p>Best regards,<br>CONNECTIONZIM Team</p>
            </div>
        ';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Update in send_email.php if database connection exists
$conn = new mysqli("localhost", "root", "", "connectionzim");
?> 