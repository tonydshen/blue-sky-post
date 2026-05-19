# Backend Update Guide for Blue Sky Post

## Overview
The mobile app now sends post title and description to the backend. You need to update `test3.php` to:
1. Capture the `post_title` and `post_description` fields
2. Send an email to `support@datacommlab.com` with all post information

## Fields Sent from Mobile App
The app now sends the following additional fields:
- `post_title` - Post title (max 100 characters)
- `post_description` - Post description (max 500 characters)

All existing fields are still sent as before:
- User profile: `fname`, `lname`, `phone`, `email`, `weixin`, `address`
- Location setting: `use_location`
- Media files: `file_0`, `file_1`, etc. with corresponding captions `caption_0`, `caption_1`, etc.

## PHP Code Updates

### Step 1: Capture the new fields
Add this to the beginning of your request processing in test3.php:

```php
// Capture post information
$postTitle = isset($_POST['post_title']) ? sanitize_input($_POST['post_title']) : '';
$postDescription = isset($_POST['post_description']) ? sanitize_input($_POST['post_description']) : '';

// Existing profile fields
$fname = isset($_POST['fname']) ? sanitize_input($_POST['fname']) : '';
$lname = isset($_POST['lname']) ? sanitize_input($_POST['lname']) : '';
$phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
$weixin = isset($_POST['weixin']) ? sanitize_input($_POST['weixin']) : '';
$address = isset($_POST['address']) ? sanitize_input($_POST['address']) : '';
$useLocation = isset($_POST['use_location']) ? $_POST['use_location'] : '0';
```

### Step 2: Prepare email content
Add this to prepare the email with all information:

```php
// Prepare email content
$emailSubject = "New Blue Sky Post Submission";
$emailBody = "A new post has been submitted with the following details:\n\n";

$emailBody .= "=== POST INFORMATION ===\n";
$emailBody .= "Title: " . $postTitle . "\n";
$emailBody .= "Description: " . $postDescription . "\n\n";

$emailBody .= "=== USER PROFILE ===\n";
$emailBody .= "Name: " . $fname . " " . $lname . "\n";
$emailBody .= "Email: " . $email . "\n";
$emailBody .= "Phone: " . $phone . "\n";
$emailBody .= "WeChat ID: " . $weixin . "\n";
$emailBody .= "Address: " . $address . "\n";
$emailBody .= "Location Sharing Enabled: " . ($useLocation == '1' ? 'Yes' : 'No') . "\n\n";

$emailBody .= "=== MEDIA INFORMATION ===\n";
// Count uploaded files
$fileCount = 0;
for ($i = 0; isset($_FILES["file_$i"]); $i++) {
    $fileCount++;
    $caption = isset($_POST["caption_$i"]) ? $_POST["caption_$i"] : '';
    $emailBody .= "File " . ($i + 1) . ": " . $_FILES["file_$i"]['name'];
    if (!empty($caption)) {
        $emailBody .= " - Caption: " . $caption;
    }
    $emailBody .= "\n";
}
$emailBody .= "Total Files: " . $fileCount . "\n\n";

// Add submission timestamp
$emailBody .= "=== SUBMISSION DETAILS ===\n";
$emailBody .= "Submitted at: " . date('Y-m-d H:i:s') . "\n";
$emailBody .= "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";
```

### Step 3: Send the email
Add this to send the email to support@datacommlab.com:

```php
// Send email to support
$to = "support@datacommlab.com";
$headers = "From: noreply@datacommlab.com\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$mailSent = mail($to, $emailSubject, $emailBody, $headers);

if (!$mailSent) {
    // Log the error but don't fail the upload
    error_log("Failed to send email for post submission");
}
```

### Step 4: Store post information in database (optional)
If you have a database, consider storing this information:

```php
// Example database insertion
if ($mailSent && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO posts (title, description, fname, lname, email, phone, weixin, address, upload_date) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$postTitle, $postDescription, $fname, $lname, $email, $phone, $weixin, $address]);
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
    }
}
```

## Testing Checklist
- [ ] Submit a post with title and description
- [ ] Verify email is received at support@datacommlab.com
- [ ] Email contains correct post title and description
- [ ] Email contains all user profile information
- [ ] Email lists all uploaded files
- [ ] App receives success response and shows post URL
- [ ] Post URL works and displays correctly

## Notes
- Make sure your server allows sending emails from PHP (check mail() function availability)
- Consider using a mailing library like PHPMailer or SwiftMailer for better reliability
- Add appropriate error handling and logging
- Sanitize all inputs to prevent injection attacks
- Consider adding database logging for audit purposes
