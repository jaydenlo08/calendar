<?php
header("Content-type: text/plain; charset=utf-8");
// header("Content-Disposition: attachment; filename=weather.ics");

// Location
if (isset($_GET["city"])) {
    $city = $_GET["city"];
} else {
    exit;
}

function dateToCal($dateString = "", $offset = "") {
    $date = new DateTime($dateString . $offset);
    return $date->format("Ymd");
}
function WWToIcon($ww) {
    switch($ww) {
        case 0:
            // Sunny
            $emoji = "☀️";
            break;
        case 1: case 2: case 3:
            // Cloudy
            $emoji = "☁️";
            break;
        case 45: case 48:
            // Foggy
            $emoji = "🌫";
            break;
        case 51: case 53: case 55: case 61: case 63: case 65: case 80:  case 81: case 82:
            // Drizzle, rain, rain shower
            $emoji = "🌦";
            break;
        case 56: case 57: case 66: case 67: case 71: case 73: case 75: case 77: case 85: case 86:
            // Freezing drizzle, rain, snow, snow shower
            $emoji = "❄️";
            break;
        case 95: case 96: case 99:
            // Thunder
            $emoji = "⛈";
            break;
        default:
            // Unknown
            $emoji = "🤔";
            break;
    }
    return $emoji;
}
function WWToDesc($ww) {
    switch($ww) {
        case 0: $desc = "Clear sky"; break;
        case 1: $desc = "Mainly clear"; break;
        case 2: $desc = "Partly cloudy"; break;
        case 3: $desc = "Overcast"; break;
        case 45: $desc = "Fog"; break;
        case 48: $desc = "Depositing rime fog"; break;
        case 51: $desc = "Light drizzle"; break;
        case 53: $desc = "Moderate drizzle"; break;
        case 55: $desc = "Dense drizzle"; break;
        case 56: $desc = "Light freezing drizzle"; break;
        case 57: $desc = "Dense freezing drizzle"; break;
        case 61: $desc = "Slight rain"; break;
        case 63: $desc = "Moderate rain"; break;
        case 65: $desc = "Heavy rain"; break;
        case 66: $desc = "Light freezing rain"; break;
        case 67: $desc = "Heavy freezing rain"; break;
        case 71: $desc = "Slight snow fall"; break;
        case 73: $desc = "Moderate snow fall"; break;
        case 75: $desc = "Heavy snow fall"; break;
        case 77: $desc = "Snow grains"; break;
        case 80: $desc = "Slight rain showers"; break;
        case 81: $desc = "Moderate rain showers"; break;
        case 82: $desc = "Violent rain showers"; break;
        case 85: $desc = "Slight snow showers"; break;
        case 86: $desc = "Heavy snow showers"; break;
        case 95: $desc = "Slight or moderate thunderstorm"; break;
        case 96: $desc = "Thunderstorm with slight hail"; break;
        case 99: $desc = "Thunderstorm with heavy hail"; break;
        default: $desc = "Unknown weather"; break;
    }
    return $desc;
}
function hourlyAvg($array, $day) {
    $slicedArray = array_slice($array, $day * 24, 24, true);
    $avg = round(array_sum($slicedArray) / count($slicedArray), 1);
    return($avg);
}
?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//jaydenlo08/JCalendar//EN
X-WR-CALNAME:Weather in <?=$city ."
"?>
X-APPLE-CALENDAR-COLOR:#149EDC
CALSCALE:GREGORIAN
<?php
// Download data
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
if (@fsockopen("api.open-meteo.com", 443)) {
    $geoCoder = json_decode(file_get_contents("https://geocoding-api.open-meteo.com/v1/search?count=1&name=" . rawurlencode($city)), true);
    // Set the timezone
    $timezone = $geoCoder["results"][0]["timezone"]; date_default_timezone_set($timezone);
    $startDate = new DateTime();
    $endDate = new DateTime("+7 Days");
    $weather = json_decode(file_get_contents("https://api.open-meteo.com/v1/forecast?latitude=" . $geoCoder["results"][0]["latitude"] .
                                "&longitude=" . $geoCoder["results"][0]["longitude"] .
                                "&daily=weathercode,temperature_2m_max,temperature_2m_min,sunrise,sunset,winddirection_10m_dominant&timezone=" . $timezone .
                                "&hourly=windspeed_10m,precipitation_probability,relativehumidity_2m,surface_pressure,visibility,apparent_temperature" .
                                "&start_date=" . $startDate->format("Y-m-d") .
                                "&end_date=" . $endDate->format("Y-m-d")), true);
} else {
    exit;
}

// Loop through all days
for ($i = 0; $i < count($weather["daily"]["time"]); $i++) {
    $dateDiff = date_create($weather["daily"]["time"][0])->diff(date_create($weather["daily"]["time"][$i]), true)->format("%a");
    //for ($hour = 0; $hour )
    $directions = array("N", "NNE", "NE", "ENE", "E", "ESE", "SE", "SSE", "S", "SSW", "SW", "WSW", "W", "WNW", "NW", "NNW", "N");
    $directionIcons = array("↓", "↙", "←", "↖", "↑", "↗", "→", "↘", "↓");
    $description =  "Weather in $city:" . '\n' .
                    "Feels like " . hourlyAvg($weather["hourly"]["apparent_temperature"], $dateDiff) . '°C\n' .
                    WWToIcon($weather["daily"]["weathercode"][$i]) . " " .
                    WWToDesc($weather["daily"]["weathercode"][$i]) . '\n' .
                   "🌅 Sunrise: " . date("H:i", strtotime($weather["daily"]["sunrise"][$i])) . '\n' .
                   "🌇 Sunset: " . date("H:i", strtotime($weather["daily"]["sunset"][$i])) . '\n' .
                   "🌧️ Precipitation: " . hourlyAvg($weather["hourly"]["precipitation_probability"], $dateDiff) . '%\n' .
                   "💧 Humidity: " . hourlyAvg($weather["hourly"]["relativehumidity_2m"], $dateDiff) . '%\n' .
                   "⚡️ Pressure: " . hourlyAvg($weather["hourly"]["surface_pressure"], $dateDiff) . 'hPa\n' .
                   "🌁 Visibility: " . hourlyAvg($weather["hourly"]["visibility"], $dateDiff) / 1000 . 'km\n' .
                   "💨 Wind Speed: " . hourlyAvg($weather["hourly"]["windspeed_10m"], $dateDiff) . 'km/h\n' .
                   "🚩 Wind Direction: " . $directionIcons[round($weather["daily"]["winddirection_10m_dominant"][$i] / 45)] . " " .
                                        $directions[round($weather["daily"]["winddirection_10m_dominant"][$i] / 22.5)];
?>
BEGIN:VEVENT
SUMMARY:<?= WWToIcon($weather["daily"]["weathercode"][$i]) . round($weather["daily"]["temperature_2m_max"][$i]) ?>°/<?= round($weather["daily"]["temperature_2m_min"][$i]) ?>°
CONTACT:Jayden Lo
UID:<?= dateToCal($weather["daily"]["time"][$i]) ?>@jaydenlo08
DTSTAMP;VALUE=DATE:<?= dateToCal() . "
" ?>
DTSTART;VALUE=DATE:<?= dateToCal($weather["daily"]["time"][$i]) . "
" ?>
DTEND;VALUE=DATE:<?= dateToCal($weather["daily"]["time"][$i], "+1 day") . "
" ?>
DESCRIPTION;LANGUAGE=en:<?= $description . "
" ?>
X-EMOJI:<?= WWToIcon($weather["daily"]["weathercode"][$i]) . "
" ?>
END:VEVENT
<?php
}
?>
END:VCALENDAR