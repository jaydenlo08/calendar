<?php
/**
 * update.php
 * 
 * Update local cached calendars
 * 
 * @package   JCalendar
 * @author    Jayden Lo
 * @copyright 2022 Jayden Lo
 * @version 1.0
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once './config.php';
include './config.user.php';

foreach ($remote_urls as $remote_url_key => $remote_url) {
    $iCalendar = file_get_contents($remote_url, false) or die("Unable to get contents from remote server");
    $file = fopen('calendars/' . $remote_url_key . ".ics", "w") or die("Unable to open local file");
    fwrite($file, $iCalendar) or die("Unable to write local file");
    fclose($file) or die("Unable to close local file");
}
print("Successfully updated cached local version")
?>
