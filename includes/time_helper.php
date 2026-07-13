<?php
function getSriLankaTime() {
    $timezone = new DateTimeZone('Asia/Colombo');
    $datetime = new DateTime('now', $timezone);
    return $datetime;
}

function getSriLankaHour() {
    $datetime = getSriLankaTime();
    return (int)$datetime->format('H');
}

function formatSriLankaDateTime($format = 'Y-m-d H:i:s') {
    $datetime = getSriLankaTime();
    return $datetime->format($format);
}

function convertToSriLankaTime($dateTimeString) {
    $timezone = new DateTimeZone('Asia/Colombo');
    $datetime = new DateTime($dateTimeString, $timezone);
    return $datetime;
}
?>
