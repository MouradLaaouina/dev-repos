<?php
/**
 * Document wrapper for customerbalance module
 * Allows viewing/downloading PDF documents
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$file = GETPOST('file', 'alpha');
$action = GETPOST('action', 'aZ09');

// Security check
if (!$user->hasRight('customerbalance', 'read')) {
    accessforbidden();
}

// Define output directory
$upload_dir = DOL_DATA_ROOT.'/customerbalance';

if ($file) {
    // Clean file path to prevent directory traversal
    $file = basename($file);
    $fullpath = $upload_dir.'/'.$file;

    // Check file exists
    if (!file_exists($fullpath)) {
        header('HTTP/1.0 404 Not Found');
        print 'File not found: '.$file;
        exit;
    }

    // Get file info
    $filename = basename($fullpath);
    $filesize = filesize($fullpath);
    $filetype = dol_mimetype($filename);

    // Set headers for PDF viewing
    header('Content-Type: '.$filetype);
    header('Content-Length: '.$filesize);

    // For inline viewing (not download)
    if ($action != 'download') {
        header('Content-Disposition: inline; filename="'.$filename.'"');
    } else {
        header('Content-Disposition: attachment; filename="'.$filename.'"');
    }

    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output file
    readfile($fullpath);
    exit;
}

// No file specified
header('HTTP/1.0 400 Bad Request');
print 'No file specified';
exit;
