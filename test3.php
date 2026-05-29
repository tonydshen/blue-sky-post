<?php
// test3.php - Blue Sky Post Receiver & BSWP Automation Pipeline
// Created: 05/06/2026
// Updated: 05/25/2026 - Integrated Multi-language Translation Engine & Configuration Replacements
// Updated: 05/25/2026 - extract_media_components and create_bswp_script functions revised

ini_set('display_errors', 0); // Keep clean JSON outputs for the mobile app
error_reporting(E_ALL);

// 1. CONFIGURATION
$baseDir = "/var/www/html/tmp/php/uploads/";
$baseUrl = "https://datacommlab.com/tmp/php/uploads/";
$adminEmail = "support@datacommlab.com";

$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '/var/www/html';
$templatePath = $docRoot . "/booths/scripts/template2.php";

// 2. CAPTURE INCOMING DATA
$postTitle       = $_POST['post_title']       ?? 'Default Blue Sky Post';
$postDescription = $_POST['post_description'] ?? '';
$fname   = $_POST['fname']   ?? 'Guest';
$lname   = $_POST['lname']   ?? 'User';
$email   = $_POST['email']   ?? '';
$phone   = $_POST['phone']   ?? '';
$weixin  = $_POST['weixin']  ?? '';
$address = $_POST['address'] ?? '';
$locPerm = $_POST['use_location'] ?? '0';

$locationText = ($locPerm === '1') ? "Houston, TX" : "my phone";
$currentDate = date("F j, Y, g:i a");

// 3. CREATE UNIQUE TARGET DIRECTORY
$folderName = preg_replace("/[^a-zA-Z0-9]/", "", $fname) . "_" .
              preg_replace("/[^a-zA-Z0-9]/", "", $lname) . "_" .
              date("YmdHis");
$targetDirFs = $baseDir . $folderName . "/"; // Filesystem path
$NEW_PATH = "/tmp/php/uploads/" . $folderName; // Apache path

if (!is_dir($targetDirFs)) {
    mkdir($targetDirFs, 0755, true);
}

// 4. PROCESS UPLOADS
$uploadedFiles = [];
$uploadedDocuments = [];

foreach ($_FILES as $key => $file) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $safeName = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $file['name']);
        $dest = $targetDirFs . $safeName;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            if (strpos($key, 'document_') === 0) {
                // Handle document uploads
                $index = str_replace('document_', '', $key);
                $docTitle = $_POST['document_title_' . $index] ?? 'Untitled Document';
                $uploadedDocuments[] = [
                    'name' => $safeName,
                    'title' => $docTitle,
                    'originalName' => $file['name']
                ];
            } else {
                // Handle regular media (photo/video)
                $index = str_replace('file_', '', $key);
                $caption = $_POST['caption_' . $index] ?? '';
                $uploadedFiles[] = ['name' => $safeName, 'caption' => $caption];
            }
        }
    }
}

// 5. DERIVE FILENAMES & SLUGS FROM TITLE
$slug = strtolower(trim($postTitle));
$slug = preg_replace("/[^a-z0-9]+/", "-", $slug);
$slug = trim($slug, "-");
if (empty($slug)) { $slug = "default-blue-sky-post"; }
$NEW_FILENAME = $slug . ".htm";

// Public crossover link addresses
$phpScriptUrl = $baseUrl . $folderName . "/scripts/bswp-" . $slug . ".php";
$indexHtmlUrl = $baseUrl . $folderName . "/index.html";

// 6. BUILD DESCRIPTION HTML (For index.html standard layout)
if (!empty($postDescription)) {
    $descHtml = "<div style='background: #f0f7ff; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px; border-radius: 5px;'>\n";
    $descHtml .= "<strong>Description:</strong><br>" . nl2br(htmlspecialchars($postDescription)) . "\n";
    $descHtml .= "</div>";
} else {
    $descHtml = "";
}

// 7. BUILD AND WRITE INDEX.HTML (With Switch Link to BSWP Script View)
$htmlContent = "
<!DOCTYPE html>
<html>
<head>
    <title>{$postTitle}</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; background: #f4f4f4; }
        .card { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #007bff; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        h2 { color: #333; font-size: 18px; margin-top: 25px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 8px; }
        .media-item { margin-bottom: 30px; }
        img, video { width: 100%; border-radius: 5px; }
        .caption { font-style: italic; color: #555; margin-top: 5px; background: #fff8e1; padding: 10px; border-left: 4px solid #ffc107; }
        .document-item { margin-bottom: 12px; }
        .document-link { display: inline-block; background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-bottom: 5px; }
        .document-link:hover { background: #218838; }
        .document-title { display: block; color: #333; font-size: 14px; margin-top: 5px; }
        .footer { font-size: 12px; color: #888; text-align: center; margin-top: 20px; }
        .switch-link { display: inline-block; background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-bottom: 20px; font-weight: bold; }
        .switch-link:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='card'>
        <a href='{$phpScriptUrl}' class='switch-link'>Switch to Marketplace View (BSWP PHP)</a>
        <h1>{$postTitle}</h1>
        {$descHtml}
        <p><strong>Posted by:</strong> {$fname} {$lname} | <strong>From:</strong> {$locationText} | <strong>Date:</strong> {$currentDate}</p>";

foreach ($uploadedFiles as $item) {
    $ext = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['mp4', 'mov', 'webm'])) {
        $htmlContent .= "<div class='media-item'><video controls src='{$item['name']}'></video>";
    } else {
        $htmlContent .= "<div class='media-item'><img src='{$item['name']}'>";
    }
    if ($item['caption']) {
        $htmlContent .= "<p class='caption'>{$item['caption']}</p>";
    }
    $htmlContent .= "</div>";
}

// Add documents section if there are any documents
if (!empty($uploadedDocuments)) {
    $htmlContent .= "<h2>📄 Documents</h2>";
    foreach ($uploadedDocuments as $doc) {
        $htmlContent .= "<div class='document-item'>";
        $htmlContent .= "<a href='{$doc['name']}' class='document-link'>📥 Download</a>";
        $htmlContent .= "<span class='document-title'>{$doc['title']}</span>";
        $htmlContent .= "</div>";
    }
}

$htmlContent .= "
        <div class='footer'>Generated by Data Communications Lab - Blue Sky Post</div>
    </div>
</body>
</html>";

file_put_contents($targetDirFs . "index.html", $htmlContent);

// 8. EXECUTE SUBSIDIARY PIPELINES (Media Extraction, Translation, Script Scaffolding)
extract_media_components($htmlContent, $targetDirFs, $NEW_PATH, $NEW_FILENAME);
translate_and_save_descriptions($postDescription, $targetDirFs, $NEW_FILENAME);
create_bswp_script($templatePath, $targetDirFs, $NEW_PATH, $postTitle, $NEW_FILENAME, $slug, $indexHtmlUrl);

// 9. RECORD PROFILE & TRANSMIT NOTIFICATION
$postcardUrl = $baseUrl . $folderName . "/index.html";
$documentsInfo = "";
if (!empty($uploadedDocuments)) {
    $documentsInfo = "\nDOCUMENTS: " . count($uploadedDocuments);
    foreach ($uploadedDocuments as $idx => $doc) {
        $documentsInfo .= "\n  Doc " . ($idx + 1) . ": " . $doc['name'] . " (" . $doc['title'] . ")";
    }
}
$profileText = "TITLE: $postTitle\nDESCRIPTION: $postDescription\nFNAME: $fname\nLNAME: $lname\nPHONE: $phone\nEMAIL: $email\nWEIXIN: $weixin\nADDRESS: $address\nURL: $postcardUrl\nFOLDER: $folderName\nDATE: $currentDate$documentsInfo";
file_put_contents($targetDirFs . "profile.txt", $profileText);

// Build Email Content
$emailSubject = "New Blue Sky Post: " . $postTitle;
$emailBody = "A new post has been submitted.\n\n";

$emailBody .= "=== POST INFORMATION ===\n";
$emailBody .= "Title: " . $postTitle . "\n";
$emailBody .= "Description: " . $postDescription . "\n\n";

$emailBody .= "=== POSTED BY ===\n";
$emailBody .= "Name: " . $fname . " " . $lname . "\n";
$emailBody .= "Email: " . $email . "\n";
$emailBody .= "Phone: " . $phone . "\n";
$emailBody .= "WeChat ID: " . $weixin . "\n";
$emailBody .= "Address: " . $address . "\n\n";

$emailBody .= "=== MEDIA INFORMATION ===\n";
$emailBody .= "Total Files: " . count($uploadedFiles) . "\n";
foreach ($uploadedFiles as $idx => $item) {
    $emailBody .= ($idx + 1) . ". " . $item['name'];
    if (!empty($item['caption'])) {
        $emailBody .= " - Caption: " . $item['caption'];
    }
    $emailBody .= "\n";
}
$emailBody .= "\n";

if (!empty($uploadedDocuments)) {
    $emailBody .= "=== DOCUMENTS ===\n";
    $emailBody .= "Total Documents: " . count($uploadedDocuments) . "\n";
    foreach ($uploadedDocuments as $idx => $doc) {
        $emailBody .= ($idx + 1) . ". " . $doc['name'] . " - Title: " . $doc['title'] . "\n";
    }
    $emailBody .= "\n";
}

$emailBody .= "=== ACCESS INFORMATION ===\n";
$emailBody .= "Post URL: " . $indexHtmlUrl . "\n";
$emailBody .= "Marketplace View URL: " . $phpScriptUrl . "\n";
$emailBody .= "Folder: " . $folderName . "\n";
$emailBody .= "Submitted: " . $currentDate . "\n";
$emailBody .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') . "\n";

$headers = "From: noreply@datacommlab.com\r\n";
if (!empty($email)) {
    $headers .= "Reply-To: " . $email . "\r\n";
}
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

mail($adminEmail, $emailSubject, $emailBody, $headers);

// 10. RESPOND TO MOBILE APP
header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'url' => $postcardUrl
]);
exit;


// ============================================================
// HELPER PIPELINE FUNCTIONS
// ============================================================

/**
 * Handles automatic text translation loops and scaffolds clean .htm files under /contents/
 */
function translate_and_save_descriptions($description, $targetDirFs, $NEW_FILENAME) {
    $languages = [
        'en' => '', // No translation needed for source language
        'es' => 'es',
        'zh' => 'zh-CN' // Google API uses zh-CN for Simplified Chinese
    ];

    foreach ($languages as $langFolder => $apiLangCode) {
        $subFolder = $targetDirFs . "contents/" . $langFolder;
        if (!is_dir($subFolder)) {
            mkdir($subFolder, 0755, true);
        }

        if ($langFolder === 'en') {
            $translatedText = $description;
        } else {
            $translatedText = call_translation_api($description, $apiLangCode);
        }

        // Convert newlines to breaks for safe inclusion into template variables
        $formattedHtml = nl2br(htmlspecialchars($translatedText));
        file_put_contents($subFolder . "/" . $NEW_FILENAME, $formattedHtml);
    }
}

/**
 * Connects securely to fallback public edge translator arrays
 */
function call_translation_api($text, $targetLang) {
    if (empty($text)) return "";

    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=" . $targetLang . "&dt=t&q=" . urlencode($text);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return $text; // Fallback to raw text if connection drops
    }

    $result = json_decode($response, true);
    if (!empty($result[0])) {
        $translatedStr = "";
        foreach ($result[0] as $sentence) {
            $translatedStr .= $sentence[0];
        }
        return $translatedStr;
    }

    return $text;
}


/**
 * Parses image/video chunks from index.html HTML and writes:
 *   images/<NEW_FILENAME>
 *   videos/<NEW_FILENAME>
 * using NEW_PATH-based URLs, no thumbnails.
 */
function extract_media_components($html, $targetDirFs, $NEW_PATH, $NEW_FILENAME) {
    // 1. Ensure target subdirectories exist
    $imagesDirFs = $targetDirFs . "images";
    $videosDirFs = $targetDirFs . "videos";

    if (!is_dir($imagesDirFs)) mkdir($imagesDirFs, 0755, true);
    if (!is_dir($videosDirFs)) mkdir($videosDirFs, 0755, true);

    // 2. Parse images: <div class='media-item'><img src='...'><p class='caption'>...</p></div>
    preg_match_all(
        "/<div class=['\"]media-item['\"]>\s*<img[^>]*src=['\"]([^'\"]+)['\"][^>]*>(?:\s*<p class=['\"]caption['\"]>(.*?)<\/p>)?\s*<\/div>/si",
        $html,
        $imgMatches,
        PREG_SET_ORDER
    );

    // 3. Parse videos: <div class='media-item'><video src='...'></video><p class='caption'>...</p></div>
    preg_match_all(
        "/<div class=['\"]media-item['\"]>\s*<video[^>]*src=['\"]([^'\"]+)['\"][^>]*>.*?<\/video>(?:\s*<p class=['\"]caption['\"]>(.*?)<\/p>)?\s*<\/div>/si",
        $html,
        $vidMatches,
        PREG_SET_ORDER
    );

    // 4. Build images/<NEW_FILENAME>
    $imgHtml = "<div class=\"gallery-block\">\n";
    foreach ($imgMatches as $m) {
        $filename = trim($m[1]);
        $caption  = isset($m[2]) ? trim($m[2]) : '';
        $imgHtml .= "    <img src=\"{$NEW_PATH}/{$filename}\" data-caption=\"" . htmlspecialchars($caption, ENT_QUOTES) . "\">\n";
    }
    $imgHtml .= "</div>\n";
    file_put_contents($imagesDirFs . "/" . $NEW_FILENAME, $imgHtml);

    // 5. Build videos/<NEW_FILENAME> (no thumbnails)
    $vidHtml = "<table>\n    <tr><th>Videos</th></tr>\n";
    foreach ($vidMatches as $m) {
        $filename = trim($m[1]);
        $caption  = isset($m[2]) ? trim($m[2]) : '';

        $vidHtml .= "    <tr>\n";
        $vidHtml .= "      <td>\n";
        $vidHtml .= "        <video class=\"gallery-video\" controls>\n";
        $vidHtml .= "            <source src=\"{$NEW_PATH}/{$filename}\" type=\"video/mp4\">\n";
        $vidHtml .= "        </video>\n";
        $vidHtml .= "        <div class=\"video-caption\">" . htmlspecialchars($caption, ENT_QUOTES) . "</div>\n";
        $vidHtml .= "      </td>\n";
        $vidHtml .= "    </tr>\n";
    }
    $vidHtml .= "</table>\n";
    file_put_contents($videosDirFs . "/" . $NEW_FILENAME, $vidHtml);
}


/**
 * Modernized BSWP Script Generator
 * - Updates template2.php safely
 * - Rewrites ONLY $NEW_PATH-based .htm references
 * - Leaves $DEFAULT_PATH references untouched
 * - Injects correct title, NEW_PATH, NEW_FILENAME
 * - Inserts switch button above <h3>
 * - Ensures images/videos/contents/links/forms all point to NEW_PATH
 */
function create_bswp_script($templatePath, $targetDirFs, $NEW_PATH, $NEW_TITLE, $NEW_FILENAME, $slug, $indexHtmlUrl) {

    if (!file_exists($templatePath)) {
        return false;
    }

    // Load template2.php
    $templateContent = file_get_contents($templatePath);

    // ------------------------------------------------------------
    // 1. Replace the PHP title line
    // ------------------------------------------------------------
    $templateContent = preg_replace(
        '/<\?php\s+\$title\s*=\s*["\'].*?["\'];/',
        '<?php $title = "' . addslashes($NEW_TITLE) . '";',
        $templateContent
    );

    // ------------------------------------------------------------
    // 2. Inject switch button ABOVE <h3>
    // ------------------------------------------------------------
    $switchButton = '<a href="' . $indexHtmlUrl . '" style="display:inline-block; background:#28a745; color:white; padding:8px 12px; text-decoration:none; border-radius:4px; font-weight:bold; margin-bottom:15px;">Switch to Standard Post View (HTML)</a><br>';

    $templateContent = preg_replace(
        '/<h3([^>]*)>.*?<\/h3>/i',
        $switchButton . '<h3$1>' . $NEW_TITLE . '</h3>',
        $templateContent
    );

    // ------------------------------------------------------------
    // 3. Replace NEW_PATH, NEW_TITLE, NEW_FILENAME variables
    // ------------------------------------------------------------
    $templateContent = preg_replace('/\$NEW_PATH\s*=\s*".*?";/',     '$NEW_PATH = "' . $NEW_PATH . '";',     $templateContent);
    $templateContent = preg_replace('/\$NEW_TITLE\s*=\s*".*?";/',    '$NEW_TITLE = "' . addslashes($NEW_TITLE) . '";', $templateContent);
    $templateContent = preg_replace('/\$NEW_FILENAME\s*=\s*".*?";/', '$NEW_FILENAME = "' . $NEW_FILENAME . '";', $templateContent);

    // ------------------------------------------------------------
    // 4. Rewrite ONLY $NEW_PATH .htm references
    //    Leave $DEFAULT_PATH references untouched
    // ------------------------------------------------------------
    $lines = explode("\n", $templateContent);

    foreach ($lines as &$line) {
        // Only rewrite if the line uses $NEW_PATH
        if (strpos($line, '$NEW_PATH') !== false && strpos($line, '.htm') !== false) {
            $line = preg_replace('/\/([^\/]+?\.(html?))";/', '/' . $NEW_FILENAME . '";', $line);
        }
    }
    unset($line);

    $templateContent = implode("\n", $lines);

    // ------------------------------------------------------------
    // 5. Ensure links directory exists and create empty link file
    // ------------------------------------------------------------
    $linksDir = $targetDirFs . "links";
    if (!is_dir($linksDir)) mkdir($linksDir, 0755, true);

    if (!file_exists($linksDir . "/" . $NEW_FILENAME)) {
        file_put_contents($linksDir . "/" . $NEW_FILENAME, "");
    }

    // ------------------------------------------------------------
    // 6. Write final bswp script
    // ------------------------------------------------------------
    $scriptsDir = $targetDirFs . "scripts";
    if (!is_dir($scriptsDir)) mkdir($scriptsDir, 0755, true);

    file_put_contents($scriptsDir . "/bswp-" . $slug . ".php", $templateContent);

    return true;
}
