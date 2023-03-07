<?php
/**
 * JCalendar
 * 
 * Show and display events from Nextcloud / ICS file
 * Optimised for e-ink display
 * Built for Kindle Browser
 * 
 * @package   JCalendar
 * @author    Jayden Lo
 * @copyright 2022 Jayden Lo
 * @version 2.0
 */

// Initialise
date_default_timezone_set('Europe/London'); // Set time zone
error_reporting(E_ALL ^ E_NOTICE); // Ignore PHP notices
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
use om\IcalParser; // Include IcalParser library
require_once './icalparser/EventsList.php';
require_once './icalparser/Freq.php';
require_once './icalparser/IcalParser.php';
require_once './icalparser/Recurrence.php';
require_once './icalparser/WindowsTimezones.php';
require_once './config.php';
include './config.user.php';
/**
 * Validate a date
 * @param DateTime $date
 * @param string $format
 * @return bool
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of
    // digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
}
function compareByTimeStamp($time1, $time2){
    $start1 = $time1['DTSTART']?->format('Hi');
    $start2 = $time2['DTSTART']?->format('Hi');
    if ($start1 > $start2)
        return 1;
    else if ($start1 < $start2) 
        return -1;
    else
        return 0;
}

// Define date variables
if (isset($_GET['date'])) {
    // Validate date
    if (!validateDate($_GET['date'])) {
        throw new Exception('Invalid date');
    }
    $todayStart = new DateTime($_GET['date']);
    $weekStart = new DateTime(
        $_GET['date'] . ' -' . $todayStart->format('w') . ' days'
    );
    $weekEnd = new DateTime(
        $_GET['date'] . ' +' . (6-$todayStart->format('w')) . ' days'
    );
} else {
    $todayStart = new DateTime();
    $weekStart = new DateTime(' -' . $todayStart->format('w') . ' days');
    $weekEnd = new DateTime(' +' . (6-$todayStart->format('w')) . ' days');
}

// Offline mode
if ($offline == true){
    $remote_urls = glob('calendars'.'/*');
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Calendar</title>
        <link rel="icon" href="data:;base64,=">
        <style>
            @font-face {
                font-family: 'NotoEmoji';
                src: url('./fonts/NotoEmoji-Regular.ttf')
            }
            span {
                display: inline-block;
            }
            body {
                font-size: 40px;
                font-family: Arial, Helvetica, sans-serif, 'NotoEmoji';
                margin: 0px;
            }
            .event {
                border: 2px solid black;
                border-radius: 20px;
                padding-left: 20px;
                padding-right: 20px;
                margin: 20px;
                cursor: pointer;
            }
            .summary {
                font-size: 50px;
            }
            .time, .date, .location {
                font-size: 40px;
                display: block;
            }
            .pastEvent {
                color: grey;
                border-color: grey;
            }
            #header {
                text-align: center;
                padding: 20px;
                position: relative;
                height: 75px;
            }
            #date-title {
                font-size: 75px;
                position: absolute;
                height: 75px;
                line-height: 75px;
                top: 0;
                left: 0;
                bottom: 0;
                right: 0;
                margin: auto;
            }
            .navigation {
                border-radius: 12px;
                border: none;
                background-color: transparent;
                width: 100px;
                height: 100px;
                transition-duration: 0.4s;
                margin: auto;
                position: absolute;
                top: 0;
                bottom: 0;
            }
            .navigation:hover {
                background-color: lightgrey;
                cursor: pointer;
            }
            .navigation:focus {
                outline:0;
                background-color: lightgrey;
            }
            .navigationIcon {
                width: 100%;
                height: 100%;
            }
            #header-divider {
                margin: 0px;
                border: 1px solid black;
            }
            .emoji {
                font-family: 'NotoEmoji';
            }
            /* The Modal (background) */
            #modal {
                display: none; /* Hidden by default */
                position: fixed; /* Stay in place */
                z-index: 1; /* Sit on top */
                padding-top: 10%; /* Location of the box */
                left: 0;
                top: 0;
                width: 100%; /* Full width */
                height: 100%; /* Full height */
                overflow: auto; /* Enable scroll if needed */
                background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            }

            /* Modal Content */
            #modal-content {
                position: relative;
                background-color: #fefefe;
                margin-left: auto;
                margin-right: auto;
                margin-bottom: 20%;
                padding: 0;
                border: 1px solid #888;
                border-radius: 20px;
                width: 80%;
                box-shadow: rgba(0, 0, 0, 0.25) 0px 54px 55px, rgba(0, 0, 0, 0.12) 0px -12px 30px, rgba(0, 0, 0, 0.12) 0px 4px 6px, rgba(0, 0, 0, 0.17) 0px 12px 13px, rgba(0, 0, 0, 0.09) 0px -3px 5px;
            }

            #modal-header {
                padding: 2px 16px;
                text-align: center;
            }

            #modal-body {
                padding: 2px 16px;
                font-size: 30px;
                overflow: auto;
            }
        </style>
    </head>
    <body onload="changeDay(null)">
        <!-- Show error when JavaScript is not supported -->
        <noscript>
            <p id="notSupported">JavaScript not supported by your browser!</p>
            <style>
                .navigation {
                    display: none;
                }
                #header {
                    display: none;
                }
                #notSupported {
                    text-align: center;
                }
            </style>
        </noscript>
        <!-- Description pop-up modal, hidden by default -->
        <div id="modal">
            <div id="modal-content">
                <h4 id="modal-header"></h4>
                <div id="modal-body"></div>
            </div>
        </div>
        <!-- All contents -->
        <div id="content">
            <!-- Header: navigation & date -->
            <div id="header">
                <div id="date-title" alt="Date"></div>
                <button class="navigation" id="nextDay" onclick="changeDay('next')" style="right: 20px" alt="Next day">
                    <svg fill="currentColor" width="27.439884" height="46.538464" viewBox="0 0 6.5855722 11.169231" class="material-design-icon__svg" version="1.1" id="svg4" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg"><path id="rect339" style="fill:#000000;stroke-width:0.24" d="m 6.2929489,6.2862962 c 0.02363,-0.02363 0.04552,-0.04831 0.06629,-0.07358 0.301771,-0.367127 0.301784,-0.895728 0,-1.262848 -0.01004,-0.01221 -0.02012,-0.02429 -0.03082,-0.03613 -7.37e-4,-8.21e-4 -0.0016,-0.0015 -0.0023,-0.0023 -0.01076,-0.01183 -0.02172,-0.02371 -0.03315,-0.03514 -4.44e-4,-4.43e-4 -8.82e-4,-8.83e-4 -0.0013,-0.0013 -4.4e-4,-4.41e-4 -8.85e-4,-8.84e-4 -0.0013,-0.0013 L 1.7105569,0.29396606 c -0.3919545,-0.391955 -1.02336346,-0.391955 -1.41531846,0 -0.391954,0.391955 -0.391623,1.02303204 3.32e-4,1.41498704 L 4.1729546,5.5862612 0.29292794,9.4662882 c -0.39057,0.39057 -0.390571,1.0194458 -10e-7,1.4100148 0.390569,0.39057 1.01944596,0.390569 1.41001546,0 L 6.2902969,6.2889482 Z" /></svg>
                </button>
                <button class='navigation' id='prevDay' onclick='changeDay("prev")' style='left: 20px' alt="Previous day">
                    <svg fill="currentColor" width="27.439888" height="46.538464" viewBox="0 0 6.5855731 11.169231" class="material-design-icon__svg" version="1.1" id="svg4" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg"><path id="rect339" style="fill:#000000;stroke-width:0.24" d="m 0.29262384,4.8829342 c -0.023631,0.02363 -0.04552,0.04831 -0.066291,0.07358 -0.301771,0.367127 -0.3017835,0.895728 1e-7,1.262848 0.010037,0.01221 0.02012,0.02429 0.030825,0.03613 7.37e-4,8.21e-4 0.00158,0.0015 0.00232,0.0023 0.01076,0.01183 0.021716,0.02371 0.033146,0.03514 4.435e-4,4.43e-4 8.817e-4,8.83e-4 0.00133,0.0013 4.401e-4,4.41e-4 8.847e-4,8.84e-4 0.00133,0.0013 L 4.8750159,10.875264 c 0.391955,0.391955 1.023364,0.391955 1.415319,0 0.391954,-0.391955 0.391623,-1.0230318 -3.32e-4,-1.4149868 L 2.4126182,5.5829692 6.2926449,1.7029422 c 0.39057,-0.3905697 0.390571,-1.01944594 1e-6,-1.41001544 -0.390569,-0.3905695 -1.019446,-0.3905692 -1.4100155,5e-7 L 0.29527554,4.8802822 Z" /></svg>
                </button>
            </div>
            <!-- Divider -->
            <hr id="header-divider">
            <!-- Loading icon when changing week, hidden by default -->
            <div id="loadIcon" style="display: none">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="100px" height="100px" style="margin:auto;background:#fff;display:block;" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid"><g transform="rotate(0 50 50)"><rect x="47" y="24" rx="3" ry="3" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.9166666666666666s" repeatCount="indefinite" /></rect></g><g transform="rotate(30 50 50)"><rect x="47" y="24" rx="3" ry="3" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.8333333333333334s" repeatCount="indefinite" /></rect></g><g transform="rotate(60 50 50)"><rect x="47" y="24" rx="3" ry="3" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.75s" repeatCount="indefinite" /></rect></g><g transform="rotate(90 50 50)"><rect x="47" y="24" rx="3" ry="3" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.6666666666666666s" repeatCount="indefinite" /></rect></g><g transform="rotate(120 50 50)"><rect x="47" y="24" rx="3" ry="3" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.5833333333333334s" repeatCount="indefinite" /></rect></g><g transform="rotate(150 50 50)"><rect x="47" y="24" rx="3" ry="3" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.5s" repeatCount="indefinite" /></rect></g><g transform="rotate(180 50 50)"><rect x="47" y="24" rx="3" ry="3" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.4166666666666667s" repeatCount="indefinite" /></rect></g><g transform="rotate(210 50 50)"><rect x="47" y="24" rx="3" ry="3" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.3333333333333333s" repeatCount="indefinite" /></rect></g><g transform="rotate(240 50 50)"><rect x="47" y="24" rx="3" ry="3" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.25s" repeatCount="indefinite" /></rect></g><g transform="rotate(270 50 50)"><rect x="47" y="24" rx="3" ry="3" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.16666666666666666s" repeatCount="indefinite" /></rect></g><g transform="rotate(300 50 50)"><rect x="47" y="24" rx="3" ry="3" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="-0.08333333333333333s" repeatCount="indefinite" /></rect></g><g transform="rotate(330 50 50)"><rect x="47" y="24" rx="3" ry="3" width="6" height="12" fill="#000000"><animate attributeName="opacity" values="1;0" keyTimes="0;1" dur="1s" begin="0s" repeatCount="indefinite" /></rect></g></svg>
            </div>
            <script>
                // Formats a date to YYYY-MM-DD
                function formatDate(date) {
                    var d = new Date(date),
                        month = '' + (d.getMonth() + 1),
                        day = '' + d.getDate(),
                        year = d.getFullYear();

                    if (month.length < 2) 
                        month = '0' + month;
                    if (day.length < 2) 
                        day = '0' + day;

                    return [year, month, day].join('-');
                }

                // Change date
                function changeDay(newDay) {
                    // Initalise variables
                    if (newDay == null) {
                        sessionStorage.removeItem("date");
                    }
                    const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                    const days = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
                    // Put saved date to variable
                    if (sessionStorage.getItem("date")==null) {
                        // On load
                        date = new Date(<?php echo($todayStart->format('Y, m-1, d')) ?>);
                    } else {
                        // Change date
                        date = new Date(sessionStorage.getItem("date"));
                    }

                    // Define dates
                    var prevWeekEnd = new Date(sessionStorage.getItem("date"));
                    prevWeekEnd.setDate(prevWeekEnd.getDate() - prevWeekEnd.getDay() - 1);
                    var nextWeekStart = new Date(sessionStorage.getItem("date"));
                    nextWeekStart.setDate(nextWeekStart.getDate() - nextWeekStart.getDay() + 7);

                    // Next day or Previous day
                    if (newDay == 'next') {
                        date.setDate(date.getDate() + 1);
                    } else if (newDay == 'prev') {
                        date.setDate(date.getDate() - 1);
                    };

                    // Display only today's events
                    events = document.getElementsByClassName('event');
                    for (var i = 0; i < events.length; i++) {
                        events[i].style.display = 'none';
                    }
                    for (var i = 0; i < events.length; i++) {
                        if ((events[i].dataset.start <= formatDate(date)) && (events[i].dataset.end >= formatDate(date))) {
                            events[i].style.display = 'block';
                        };
                    }

                    // Change title
                    document.getElementById('date-title').innerHTML = days[date.getDay()] + ' ' + date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();

                    // Change week
                    if (formatDate(date) == formatDate(prevWeekEnd)) {
                        // Hide all events
                        for (var i = 0; i < document.getElementsByClassName('event').length; i++) {
                            document.getElementsByClassName('event')[i].style.display = "none";
                        }
                        // Show loading icon
                        document.getElementById('loadIcon').style.display = 'block';
                        // Load next week's events
                        window.location.href='?date=' + formatDate(prevWeekEnd);
                        // Stop program
                        return;
                    } else if (formatDate(date) == formatDate(nextWeekStart)) {
                        // Hide all events
                        for (var i=0; i < document.getElementsByClassName('event').length; i++) {
                            document.getElementsByClassName('event')[i].style.display = "none";
                        }
                        // Show loading icon
                        document.getElementById('loadIcon').style.display = 'block';
                        // Load next week's events
                        window.location.href='?date=' + formatDate(nextWeekStart);
                        // Stop program 
                        return;
                    }

                    // Save date to session storage
                    sessionStorage.setItem("date", date);
                }

                // Show description
                function showDescription(title, description) {
                    document.getElementById('modal').style.display = 'block';
                    document.body.style.overflow = 'hidden';
                    document.getElementById('content').style.filter = 'blur(5px)';
                    document.getElementById('modal-body').innerHTML = description.replace(/\n/g, "<br />");
                    document.getElementById('modal-header').innerHTML = title;
                }

                // When the user clicks anywhere outside of the modal, close it
                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = "none";
                        document.getElementById('content').style.filter = 'none';
                        document.body.style.overflow = 'auto';
                    }
                }
            </script>
            <div id="events-container">
                <?php
                $events = array();
                foreach ($remote_urls as $remote_url_key => $remote_url) {
                    $iCalendar = file_get_contents($remote_url, false, stream_context_create(["ssl"=>array("verify_peer" => false, "verify_peer_name" => false)])) or throw new Exception("Unable to get calendar contents from server");
                    $IcalParser = new IcalParser;
                    $results = $IcalParser->parseString($iCalendar);
                    $events = array_merge($events, (array) $IcalParser->getEvents()->sorted());
                }
                usort($events, 'compareByTimeStamp');

                // Display events
                foreach ($events as $event) {    // If event is overlapping this week
                    if (($event['DTSTART']?->format('Ymd') <= $weekEnd?->format('Ymd')) &&
                    ($event['DTEND']?->format('Ymd') >= $weekStart->format('Ymd'))) {
                        $summary = (isset($event['SUMMARY'])) ? $event['SUMMARY'] : 'Untitled Event';
                        $location = (isset($event['LOCATION'])) ? $event['LOCATION'] : NULL;
                        $class = '';
                        $description = (!empty($event['DESCRIPTION'])) ? "showDescription('" . $event['SUMMARY'] . "', '" . str_replace("\n", '&#92n', addslashes($event['DESCRIPTION'])) . "')" : '';

                        if ($event['DTSTART']->diff($event['DTEND'])->format("%a") >= 1) {
                            if (($event['DTSTART']?->format('Hi') == '0000') && ($event['DTEND']?->format('Hi') == '0000')) {
                                $event['DTEND']->modify('-1 second');
                            }

                            if ($event['DTSTART']->diff($event['DTEND'])->format("%a") >= 1) {
                                // Multiple days
                                if ($event['DTSTART']?->format('Ym') == $event['DTEND']?->format('Ym')) {
                                    // Same month --> 1 - 31 January
                                    $time = $event['DTSTART']?->format('j') . ' - ' . $event['DTEND']?->format('j F');
                                } else {
                                    // Different months --> 1 January - 1 February
                                    $time = $event['DTSTART']?->format('j F') . ' - ' . $event['DTEND']?->format('j F');
                                }
                            } elseif ($event['DTSTART']->diff($event['DTEND'])->format("%a") == 0) {
                                // 1 full day --> 1 January
                                $time = $event['DTSTART']?->format('j F');
                            }
                        } else {
                            // Single day --> 12:00-13:00
                            $time = $event['DTSTART']?->format('H:i') . ' - ' . $event['DTEND']?->format('H:i');
                        }
                        printf('
                        <div style="display: none" class="event" data-start="%s"
                        data-end="%s" onclick="%s">
                            <span class="summary">%s</span>
                            <span class="time">%s</span>
                            <span class="location">%s</span>
                        </div>', $event['DTSTART']?->format('Y-m-d'), $event['DTEND']?->format('Y-m-d'), $description, $summary, $time, $location);
                    }
                }
                ?>
            </div>
        </div>
    </body>
</html>