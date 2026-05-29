<?php $title = "Pearland Chinese New Year";
ini_set('display_errors', 1);
$entext = file_get_contents($entextfilepath);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// template2.php
// full path = /booths/scripts/template2.php
// Revision History
// 05/23/26
// How instructions revised as follows:
// How to use template.php
// 1.  make a copy of template
// 2.  acquire new title. Eg. Tiny Houses
// 2.  derive name from new title for files. Eg. Tiny Houses -> tiny-houses -> tiny-houses.htm
// 4.  derive text from description. Eg. We design, make, and install wonderful tiny houses.
// 5.  create text file as translation source in English
// 6.  use translate.sh to translate from English to multiple languages.
// 7.  text filename and full path = /booths/contents/pearland-chinese-new-year.htm
//     tfile = pearland-chinese-new-year.htm
//     tpath = /booths/contents
// 8.  image file name and full path = /booths/images/pearland-chinese-new-year.htm
//     ifile = pearland-chinese-new-year.htm. The file contains a set of images, also called gallery. Images rotate when displayed.
//     ipath = /booths/images
// 9.  video filename and full path = /booths/videos/pearland-chinese-new-year.htm
//     vfile = pearland-chinese-new-year.htm. The file contains one or more videos
//     vpath = /booths/videos
// 10. link file and full path = /booths/links/pearland-chinese-new-year.htm. The file contains URL links.
//     lfile = pearland-chinese-new-year.htm
//     lpath = /booths/links
// 11. substitue h3 heading with new title
// 12. In each filename occurrence substitue template filename with new filename. Eg. pearland-chinese-new-year.htm -> tiny-houses.htm
// 13. Name new script with new filename with .php. Eg. tiny-houses.php
// 14. sfile = tiny-houses.php
// 15. spath = /booths/scripts
// 16. create new script. Eg. /booths/scripts/tiny-houses.php
// 
// 05/24/26
// 1. PAGEPATH=/booths (default /booths)
//
PAGEPATH=/tmp/php/uploads/Tony_Shen_20260523211251

// includes
$language_toggle_buttons = $_SERVER['DOCUMENT_ROOT'] . "/includes/language_toggle_buttons2.js";
$ltbs = file_get_contents($language_toggle_buttons);

$language_functions = $_SERVER['DOCUMENT_ROOT'] . "/includes/language_functions2.js";
$lfs  = file_get_contents($language_functions);

// text content
$entextfilepath = $_SERVER['DOCUMENT_ROOT'] . "/booths/contents/en/pearland-chinese-new-year.htm";
$entext = file_get_contents($entextfilepath);

$zhtextfilepath = $_SERVER['DOCUMENT_ROOT'] . "/booths/contents/zh/pearland-chinese-new-year.htm";
$zhtext = file_get_contents($zhtextfilepath);

$estextfilepath = $_SERVER['DOCUMENT_ROOT'] . "/booths/contents/es/pearland-chinese-new-year.htm";
$estext = file_get_contents($estextfilepath);

// form
$formfilepath = $_SERVER['DOCUMENT_ROOT'] . "/booths/forms/contact-bswp.htm";
$form = file_get_contents($formfilepath);

// gallery
$g1filepath = $_SERVER['DOCUMENT_ROOT'] . $PAGEPATH . "/images/images-and-videos.htm";
$g1 = file_get_contents($g1filepath);

// videos
$v1filepath = $_SERVER['DOCUMENT_ROOT'] . $PAGEPATH . "/videos/images-and-videos.htm";
$v1 = file_get_contents($v1filepath);

// links
$l1filepath = $_SERVER['DOCUMENT_ROOT'] . "/booths/links/pearland-chinese-new-year.htm";
$l1 = file_get_contents($l1filepath);

// breadcrumbs
$breadcrumbs = '
  <a href="/index.php">Home</a> >
  <span>Company Description</span>
';

$content = <<<HTML
  <main class="container" style="padding: 2rem;">

    <!-- Vertical Stack Upper -->
    <div style="width: 100%;">

      <h3 align="center">Pearland Chinese New Year</h3>
      <!-- Images -->
      <div style="margin-bottom: 40px;">
        <div id="pearland-chinese-new-year-images">
          $g1
        </div>
      </div>

      <!-- Videos -->
      <div style="margin-bottom: 40px;">
        <div id="pearland-chinese-new-year-videos" align=center>
          $v1
        </div>
      </div>

    </div>

    <hr style="margin: 40px 0;">

    <!-- Language Toggle Buttons -->
    $ltbs

    <!-- English Paragraph -->
    <div id="enText">
      <p align="left">$entext</p>
    </div>

    <!-- Chinese Paragraph -->
    <div id="zhText">
      <p align="left">$zhtext</p>
    </div>

    <!-- Spanish Paragraph -->
    <div id="esText">
      <p align="left">$estext</p>
    </div>

    <!-- Vertical Stack Lower -->
    <div style="width: 100%;">

      <!-- Links -->
      <div style="margin-bottom: 40px;">
        <div id="pearland-chinese-new-year-links">
          $l1
        </div>
      </div>

      <!-- Forms -->
      <div style="margin-bottom: 40px;">
        <div id="pearland-chinese-new-year-forms" align=center>
          $form
        </div>
      </div>

    </div>

  </main>

  <!-- Language Functions -->
  $lfs

  <script>
    function toggleMobileMenu() {
      document.querySelector(".nav-menu").classList.toggle("mobile-active");
    }

    document.querySelectorAll(".nav-item > a").forEach(item => {
      item.addEventListener("click", function(e) {
        if (window.innerWidth <= 900) {
          const parent = this.parentElement;
          parent.classList.toggle("open");
          e.preventDefault();
        }
      });
    });

    // Unified slideshow engine for all galleries
    function initGalleryRotation(gallerySelector, interval = 3000) {
        const gallery = document.querySelector(gallerySelector);
        if (!gallery) return;

        const images = gallery.querySelectorAll("img");
        if (images.length <= 1) return;

        // Create a caption element under the gallery
        const caption = document.createElement("div");
        caption.className = "gallery-caption";
        gallery.after(caption);

        let index = 0;

        // Prepare images
        images.forEach((img, i) => {
            img.style.transition = "opacity 1s ease-in-out";
            img.style.top = "0";
            img.style.left = "0";
        });

        gallery.style.position = "relative";
        gallery.style.height = "300px";

        // Set initial caption
        caption.textContent = images[0].dataset.caption || "";

        setInterval(() => {
            const nextIndex = (index + 1) % images.length;

            // Fade out
            images[index].style.opacity = 0;
            caption.style.opacity = 0;

            setTimeout(() => {
                // Switch image + caption
                images[nextIndex].style.opacity = 1;
                caption.textContent = images[nextIndex].dataset.caption || "";
                caption.style.opacity = 1;
            }, 1000);

            index = nextIndex;
        }, interval);
    }

    // Initialize both galleries
    document.addEventListener("DOMContentLoaded", () => {
        initGalleryRotation("#pearland-chinese-new-year-images .gallery-block", 4000);
    });

  </script>

HTML;

include $_SERVER['DOCUMENT_ROOT'] . "/css/layout.css";
