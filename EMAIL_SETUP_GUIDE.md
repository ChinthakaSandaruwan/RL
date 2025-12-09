# Email Configuration Guide for RentalLanka

## Overview
The registration system now sends a beautiful welcome email to new users after they successfully register.

## Email Setup Instructions

### Step 1: Configure Email Settings in .env

Open your `.env` file and update the email configuration section:

```env
# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@rentallanka.com
MAIL_FROM_NAME=RentalLanka
```

### Step 2: For Gmail Users (Recommended for Testing)

If you're using Gmail, you need to create an **App Password**:

1. **Enable 2-Factor Authentication** on your Google Account:
   - Go to https://myaccount.google.com/security
   - Enable 2-Step Verification

2. **Generate an App Password**:
   - Go to https://myaccount.google.com/apppasswords
   - Select "Mail" as the app
   - Select "Other" as the device and name it "RentalLanka"
   - Click "Generate"
   - Copy the 16-character password (without spaces)

3. **Update .env file**:
   ```env
   MAIL_USERNAME=youremail@gmail.com
   MAIL_PASSWORD=abcd efgh ijkl mnop  # Replace with your app password
   ```

### Step 3: For Other Email Providers

#### **Using cPanel Email (Production Recommended)**
```env
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME=RentalLanka
```

#### **Using Mailtrap (Development/Testing)**
```env
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@rentallanka.com
MAIL_FROM_NAME=RentalLanka
```

#### **Using SendGrid**
```env
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@rentallanka.com
MAIL_FROM_NAME=RentalLanka
```

## Features

### Welcome Email Template
- Professional, responsive HTML design
- Branded with RentalLanka colors
- Includes:
  - Personalized greeting
  - Registration confirmation
  - Key features list
  - Call-to-action button to explore the platform
  - Contact information

### Error Handling
- Registration will succeed even if email fails to send
- Errors are logged to PHP error log
- User receives appropriate success message

## Testing

To test the email functionality:

1. Configure your email settings in `.env`
2. Register a new user at: `http://localhost/RL/auth/register/`
3. Check the email inbox of the registered email address
4. Verify the welcome email was received

## Troubleshooting

### Email Not Sending?

1. **Check PHP Error Logs**:
   - Look for email-related errors in your PHP error log
   - Common location: `C:\xampp\apache\logs\error.log`

2. **Verify SMTP Settings**:
   - Ensure `MAIL_HOST` is correct
   - Verify `MAIL_PORT` (usually 587 for TLS or 465 for SSL)
   - Check `MAIL_ENCRYPTION` is set correctly

3. **Test SMTP Connection**:
   - Try using telnet to test SMTP connection:
     ```
     telnet smtp.gmail.com 587
     ```

4. **Gmail Specific Issues**:
   - Ensure 2FA is enabled
   - Ensure App Password is generated correctly
   - Check "Less secure app access" is NOT blocking
   - Verify no spaces in the app password

5. **Firewall Issues**:
   - Ensure your firewall allows outbound connections on ports 587/465
   - Some ISPs block SMTP ports

## File Structure

```
RL/
├── config/
│   └── db.php
├── services/
│   ├── sms.php
│   └── email.php          # Email service functions
├── auth/
│   └── register/
│       └── index.php      # Updated with email sending
├── PHPMailer-master/      # PHPMailer library
└── .env                   # Updated with email config
```

## Email Functions

### `send_email($to, $subject, $body, $recipientName = null)`
Send a custom email with HTML body

### `send_welcome_email($email, $name)`
Send the welcome email template to a new user

## Future Enhancements

You can create additional email templates for:
- Password reset emails
- Booking confirmation emails
- Payment receipt emails
- Notification emails
- Weekly/monthly newsletters

## Support

If you need help, check:
- PHPMailer documentation: https://github.com/PHPMailer/PHPMailer
- Gmail App Passwords: https://support.google.com/accounts/answer/185833
