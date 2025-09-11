<?php

define('MYSQL_FORMAT', 'Y-m-d H:i:s'); // Timezone is implied to be UTC

// Returns: UTC MySQL TIMESTAMP (UMT) representing the current moment.
function now_timestamptz() {
	return phenomena_format_internal(new DateTime());
}

function phenomena_parse_internal_date(string $value): ?DateTime {
	$dt = DateTime::createFromFormat(MYSQL_FORMAT, $value, new DateTimeZone('UTC'));
	if (!$dt) return null;
	$dt->setTimezone(wp_timezone());
	return $dt;
}

function phenomena_parse_iso8601_date(string $value): ?DateTime {
	return new DateTime($value); // parse iso8601, respect timezone from $value
}

function phenomena_format_iso8601(?DateTime $dt): ?string {
	if (!$dt) return null;
	$local = (clone $dt)->setTimezone(wp_timezone());
	return $dt->format(DateTimeInterface::ATOM); // ISO 8601
}

function phenomena_format_internal(?DateTime $dt): ?string {
	if (!$dt) return null;
	$utc = (clone $dt)->setTimezone(new DateTimeZone('UTC'));
	return $utc->format(MYSQL_FORMAT);
}

