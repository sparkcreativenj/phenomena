<?php

define('MYSQL_FORMAT', 'Y-m-d H:i:s');
define('HTML5_FORMAT', 'Y-m-d\TH:i');

// Returns a DateTimeZone repr of WordPress's built-in
// time zone setting.
function phenomena_wp_timezone() {
    $timezone_string = get_option('timezone_string');

    if (!empty($timezone_string)) {
        return new DateTimeZone($timezone_string);
    }

    $offset = get_option('gmt_offset');
    if (!empty($offset)) {
        $hours   = (int)$offset;
        $minutes = abs(($offset - (int) $offset ) * 60);
        return new DateTimeZone(sprintf('%+03d:%02d', $hours, $minutes));
    }

    return new DateTimeZone('UTC');
}

// Returns: UTC MySQL TIMESTAMP (UMT) representing the current moment.
function now_timestamptz() {
    $ts = new DateTime(null, new DateTimeZone('UTC'));
    return $ts->format(MYSQL_FORMAT);
}

// Takes: UTC MySQL TIMESTAMP (UMT)
// Returns: Localized DateTime object.
function parse_utc_to_object($str) {
	if (!$str) return null;
	$ts = DateTime::createFromFormat(MYSQL_FORMAT, $str, new DateTimeZone('UTC'));
	$ts->setTimezone(phenomena_wp_timezone());
	return $ts;
}

// Takes: UTC MySQL TIMESTAMP (UMT)
// Returns: HTML5 localised timestamp (HLT)
// Notes: The HLT is formatted for use with <input type='datetime-local' />
function parse_utc($s, $to_format = HTML5_FORMAT) {
	$d = parse_utc_to_object($s);
	if (!$d) return null;
	return $d->format($to_format);
}

// Takes: HTML5 timestamp string (in WP's timezone)
// Returns a UTC MySQL TIMESTAMP string version of $html5_str
// nota bene: parse_html5(parse_utc(mysql_date)) == mysql_date
function parse_html5_to_object($html5_str) {
    if (!$html5_str) return null;
    $d = DateTime::createFromFormat(HTML5_FORMAT, $html5_str, phenomena_wp_timezone());
    $d->setTimezone(new DateTimeZone('UTC'));
    return $d;
}

// Takes: HTML5 timestamp string (in WP's timezone)
// Returns a UTC MySQL TIMESTAMP string version of $html5_str
// nota bene: parse_html5(parse_utc(mysql_date)) == mysql_date
function parse_html5($html5_str, $to_format=MYSQL_FORMAT) {
    $d = parse_html5_to_object($html5_str);
    if (!$d) return null;
    return $d->format($to_format);
}

