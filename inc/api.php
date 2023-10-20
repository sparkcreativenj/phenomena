<?php

/*
  Developer API
*/

function phenomena_get_events($offset = 0, $count = 10, $past = false) {
	if ($count == 0) return [];

	$now = now_timestamptz();

	$q = new WP_Query([
		'post_status' => 'publish',
		'post_type' => PHENOMENA_POST_TYPE,
		'meta_query' => [[
			'relation' => 'OR',
			'end_date_clause' => [
				'relation' => 'AND',
				'edc_end_exists' => [
					'key' => 'event_end_timestamp',
					'type' => 'DATETIME',
					'compare' => 'EXISTS'
				],
				'edc_compare' => [
					'key' => 'event_end_timestamp',
					'type' => "DATETIME",
					'value' => $now,
					'compare' => $past ? '<' : '>='
				]
			],
			'start_date_clause' => [
				'relation' => 'AND',
				'sdc_start_exists' => [
					'key' => 'event_start_timestamp',
					'type' => 'DATETIME',
					'compare' => 'EXISTS'
				],
				'sdc_compare' => [
					'key' => 'event_start_timestamp',
					'type' => "DATETIME",
					'value' => $now,
					'compare' => $past ? "<" : '>='
				]
			],
		]],
		'orderby' => [
			'edc_compare' => $past ? 'DESC' : "ASC",
			'sdc_compare' => $past ? 'DESC' : 'ASC'
		],
		'posts_per_page' => $count,
		'offset' => $offset
	]);
	return $q->get_posts();
}

/*
 * phenomena_get_past_events()
 * 
 * Returns an array of WP_Post objects representing past events.
 *
 */
function phenomena_get_past_events($offset = 0, $count = 10) {
	return phenomena_get_events($offset, $count, true);
}

/*
 * phenomena_get_upcoming_events()
 * 
 * Returns an array of WP_Post objects representing upcoming events.
 *
 */
function phenomena_get_upcoming_events($offset = 0, $count = 10) {
	return phenomena_get_events($offset, $count, false);
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

