<?php

/*
  Developer API
*/


/*
 * phenomena_get_upcoming_events()
 * 
 * Returns an array of WP_Post objects representing upcoming events.
 *
 */
function phenomena_get_upcoming_events($offset = 0, $count = 10) {
	$now = now_timestamptz();    

	return phenomena_get_posts([
		'post_status' => 'publish',
		'post_type' => PHENOMENA_POST_TYPE,
		'meta_key' => 'event_start_timestamp',
		'meta_type' => 'DATETIME',
		'meta_value' => $now,
		'meta_compare' => '>=',
		'order' => "ASC",
		'orderby' => 'meta_value',
		'posts_per_page' => $count,
		'offset' => $offset
	]);
}

/*
 * phenomena_get_past_events()
 * 
 * Returns an array of WP_Post objects representing past events.
 *
 */
function phenomena_get_past_events($offset = 0, $count = 10) {
	$now = now_timestamptz();    

	return phenomena_get_posts([
		'post_status' => 'publish',
		'post_type' => PHENOMENA_POST_TYPE,
		'meta_key' => 'event_end_timestamp',
		'meta_type' => 'DATETIME',
		'meta_value' => $now,
		'meta_compare' => '<=',
		'order' => "ASC",
		'orderby' => 'meta_value',
		'posts_per_page' => $count,
		'offset' => $offset
	]);
}

/*
 * phenomena_get_start_date($event)
 * 
 * Takes: a WP_Post or post id
 * Returns a DateTime object representing the start date of $event.
 *
 */
function phenomena_get_start_date($event) {
	if (!$event) return null;
	return parse_utc_to_object(phenomena_get_post_meta($event, 'event_start_timestamp'));
}

/*
 * phenomena_get_end_date($event)
 * 
 * Takes: a WP_Post or post id
 * Returns a DateTime object representing the end date of $event.
 *
 */
function phenomena_get_end_date($event) {
	if (!$event) return null;
	return parse_utc_to_object(phenomena_get_post_meta($event, 'event_end_timestamp'));
}

// The rest of the functions here are like phenomena_get_{start,end}_date,
// except they all return strings. They take the same $event argument, though.

function phenomena_get_location_name($event) {
	if (!$event) return null;
	return phenomena_get_post_meta($event, 'event_location_name');
}

function phenomena_get_city($event) {
	if (!$event) return null;
	return phenomena_get_post_meta($event, 'event_city');
}

function phenomena_get_state($event) {
	if (!$event) return null;
	return phenomena_get_post_meta($event, 'event_state');
}

function phenomena_get_country($event) {
	if (!$event) return null;
	return phenomena_get_post_meta($event, 'event_country');
}

function phenomena_get_street($event) {
	if (!$event) return null;
	return phenomena_get_post_meta($event, 'event_street');
}

function phenomena_get_zip($event) {
	if (!$event) return null;
	return phenomena_get_post_meta($event, 'event_zip');
}

function phenomena_get_more_info_url($event) {
	if (!$event) return null;
	return phenomena_get_post_meta($event, 'event_more_info_url');
}

