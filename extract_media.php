<?php
/**
 * Revision history
 * 05/23/26
 * Created from conversation with Edge Copilot
 * extract_media.php — Updated for test3.php workflow
 * Performs:
 * 1. Detect index.html filesystem path
 * 2. Compute Apache path (/var/www/html → /)
 * 3. Create images/ and videos/ directories
 * 4. Extract images + captions
 * 5. Extract videos + captions
 * 6. Derive output filename from <title>
 * 7. Write images + videos .htm files into correct subfolders
 */

// ------------------------------------------------------------
// 1. IDENTIFY index.html PATHS
// ------------------------------------------------------------
$indexFile = __DIR__ . "/index.html";   // filesystem path
if (!file_exists($indexFile)) {
    die("ERROR: index.html not found in this directory.\n");
}

// Apache document root
$docRoot = "/var/www/html";

// Convert filesystem path → Apache path
$apachePath = str_replace($docRoot, "", __DIR__);
// Example: /tmp/php/uploads/Tony_Shen_20260523211251

// ------------------------------------------------------------
// 2. READ index.html
// ------------------------------------------------------------
$html = file_get_contents($indexFile);

// ------------------------------------------------------------
// 3. EXTRACT <title> TO DERIVE OUTPUT FILENAME
// ------------------------------------------------------------
if (preg_match("/<title>(.*?)<\/title>/si", $html, $m)) {
    $title = trim($m[1]);
} else {
    $title = "media";
}

// Convert title → filename (images-and-videos.htm)
$slug = strtolower(trim($title));
$slug = preg_replace("/[^a-z0-9]+/", "-", $slug);
$slug = trim($slug, "-");
$finalFilename = $slug . ".htm";

// ------------------------------------------------------------
// 4. CREATE images/ AND videos/ DIRECTORIES
// ------------------------------------------------------------
$imagesDirFs = __DIR__ . "/images";
$videosDirFs = __DIR__ . "/videos";

if (!is_dir($imagesDirFs)) mkdir($imagesDirFs, 0755, true);
if (!is_dir($videosDirFs)) mkdir($videosDirFs, 0755, true);

// Apache paths
$imagesDirApache = $apachePath . "/images";
$videosDirApache = $apachePath . "/videos";

// ------------------------------------------------------------
// 5. PARSE IMAGES
// ------------------------------------------------------------
preg_match_all(
    "/<img[^>]*src=['\"]([^'\"]+)['\"][^>]*>\\s*<p class='caption'>(.*?)<\\/p>/si",
    $html,
    $imgMatches,
    PREG_SET_ORDER
);

// ------------------------------------------------------------
// 6. PARSE VIDEOS
// ------------------------------------------------------------
preg_match_all(
    "/<video[^>]*src=['\"]([^'\"]+)['\"][^>]*>.*?<\\/video>\\s*<p class='caption'>(.*?)<\\/p>/si",
    $html,
    $vidMatches,
    PREG_SET_ORDER
);

// ------------------------------------------------------------
// 7. BUILD images.htm
// ------------------------------------------------------------
$imgHtml = "<div class=\"gallery-block\">\n";

foreach ($imgMatches as $m) {
    $filename = trim($m[1]);
    $caption  = trim($m[2]);

    // Use Apache path
    $imgHtml .= "    <img src=\"{$apachePath}/{$filename}\" data-caption=\"{$caption}\">\n";
}

$imgHtml .= "</div>\n";

// Write file
file_put_contents($imagesDirFs . "/" . $finalFilename, $imgHtml);


// ------------------------------------------------------------
// 8. BUILD videos.htm
// ------------------------------------------------------------
$vidHtml = "<table>\n";
$vidHtml .= "    <tr><th>Videos</th></tr>\n";

foreach ($vidMatches as $m) {
    $filename = trim($m[1]);
    $caption  = trim($m[2]);

    // Thumbnail rule: filename.mp4 → filename-thumbnail.jpg
    $thumb = preg_replace('/\\.mp4$/i', '-thumbnail.jpg', $filename);

    $vidHtml .= "    <tr>\n";
    $vidHtml .= "      <td>\n";
    $vidHtml .= "        <video class=\"gallery-video\" controls poster=\"{$videosDirApache}/{$thumb}\">\n";
    $vidHtml .= "            <source src=\"{$apachePath}/{$filename}\" type=\"video/mp4\">\n";
    $vidHtml .= "        </video>\n";
    $vidHtml .= "        <div class=\"video-caption\">{$caption}</div>\n";
    $vidHtml .= "      </td>\n";
    $vidHtml .= "    </tr>\n";
}

$vidHtml .= "</table>\n";

// Write file
file_put_contents($videosDirFs . "/" . $finalFilename, $vidHtml);


// ------------------------------------------------------------
// DONE
// ------------------------------------------------------------
echo "Media extraction complete.\n";
echo "Images written to: {$imagesDirApache}/{$finalFilename}\n";
echo "Videos written to: {$videosDirApache}/{$finalFilename}\n";

?>
