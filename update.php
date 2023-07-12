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
chdir(dirname(__FILE__));

require_once './config.php';
if (file_exists("./config.user.php")) {
    include './config.user.php';
}

if (!file_exists('calendars')) {
    mkdir('calendars', 0777, true);
}
if (isset($_GET['city'])) {
    $remote_urls['weather'] = 'http' . (empty($_SERVER['HTTPS'])?'':'s') . '://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . '/weather.php?city=' . urlencode($_GET['city']);
}
foreach ($remote_urls as $remote_url_key => $remote_url) {
    $iCalendar = file_get_contents($remote_url, false, stream_context_create(["ssl"=>array("verify_peer" => false, "verify_peer_name" => false)])) or die("Unable to get contents from remote server");
    $file = fopen('calendars/' . $remote_url_key . ".ics", "w") or die("Unable to open local file");
    fwrite($file, $iCalendar) or die("Unable to write local file");
    fclose($file) or die("Unable to close local file");
}
print("Successfully updated cached local version\n");
?>
<!DOCTYPE html>