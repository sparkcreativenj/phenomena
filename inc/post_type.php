<?php

// Allow users to define their own post type for events.
if (!defined("PHENOMENA_POST_TYPE")) define('PHENOMENA_POST_TYPE', 'event');

// Allow users to define their own name for events in labels.
if (!defined('PHENOMENA_EVENT_SINGULAR')) define("PHENOMENA_EVENT_SINGULAR", 'Event');
if (!defined('PHENOMENA_EVENT_PLURAL')) define("PHENOMENA_EVENT_PLURAL", 'Events');

// Allow users to define a different descriptions for events.
if (!defined('PHENOMENA_EVENT_DESCRIPTION')) define('PHENOMENA_EVENT_DESCRIPTION', '');

// Allow users to define a different icon for events.
if (!defined('PHENOMENA_EVENT_ICON')) define('PHENOMENA_EVENT_ICON', 'dashicons-calendar');

// Allow users to define a different URL slug for events.
if (!defined('PHENOMENA_EVENT_SLUG')) define('PHENOMENA_EVENT_SLUG', 'events');

// Allow users to define a different menu position for the post type
if (!defined('PHENOMENA_EVENT_MENU_POSITION')) define('PHENOMENA_EVENT_MENU_POSITION', 5);

// Allow users to define a different taxonomy slud
if (!defined('PHENOMENA_EVENT_CATEGORY_SLUG')) define('PHENOMENA_EVENT_CATEGORY_SLUG', 'event_category');

add_action('init', function() {
	register_post_type(PHENOMENA_POST_TYPE, [
		'labels'        => [
			'name'          => __(PHENOMENA_EVENT_PLURAL),
			'singular_name' => __(PHENOMENA_EVENT_SINGULAR),
			'add_new'       => __('Add New ' . PHENOMENA_EVENT_SINGULAR),
			'add_new_item'  => __('Add New ' . PHENOMENA_EVENT_SINGULAR),
			'edit_item'     => __('Edit ' . PHENOMENA_EVENT_SINGULAR),
			'new_item'      => __('New ' . PHENOMENA_EVENT_SINGULAR),
			'all_items'     => __('All ' . PHENOMENA_EVENT_PLURAL),
			'view_item'     => __('View ' . PHENOMENA_EVENT_SINGULAR),
			'view_items'    => __('View ' . PHENOMENA_EVENT_PLURAL),
			'search_items'  => __('Search ' . PHENOMENA_EVENT_PLURAL)
		],
		'menu_icon'     => PHENOMENA_EVENT_ICON,
		'description'   => __(PHENOMENA_EVENT_DESCRIPTION),
		'rewrite'	    => ['slug' => PHENOMENA_EVENT_SLUG, 'with_front' => false],
		'menu_position' => PHENOMENA_EVENT_MENU_POSITION,
		'supports'      => ['title', 'editor', 'thumbnail', 'custom-fields', 'excerpt'],
		'public'        => true,
	        'show_in_rest'  => true,
		'has_archive'   => true
	]);

	register_taxonomy(PHENOMENA_EVENT_CATEGORY_SLUG, PHENOMENA_POST_TYPE, [
		'labels' => [
			'name' => 'Event Categories',
			'singular_name' => 'Event Category',
			'search_items' => 'Search Event Categories',
			'popular_items' => 'Popular Event Categories',
			'all_items' => 'All Event Categories',
			'parent_item' => 'Parent Event Category',
			'edit_item' => 'Edit Event Category',
			'view_item' => 'View Event Category',
			'update_item' => 'Update Event Category',
			'add_new_item' => 'Add New Event Category',
			'new_item_name' => 'New Event Category',
			'separate_items_with_commas' => 'Separate Event Categories with commas',
			'add_or_remove_items' => 'Add or remove Event Categories',
			'choose_from_most_used' => 'Choose from the most used Event Categories',
			'not_found' => 'No Event Categories found',
			'no_terms' => 'No Event Categories',
			'most_used' => 'Most Used Event Categories',
			'back_to_items' => 'Back to Event Categories'
		],
		'public' => true,
		'show_in_rest' => true
	]);
});

if (!is_admin()) {
	// The non-admin implementation of event ordering.
	// This is designed to make events appear in a
	// chronological order that makes intuitive sense.
	add_action('request', function($query_vars) {
		$dummy_q = new WP_Query($query_vars);
		if ($dummy_q->is_post_type_archive(PHENOMENA_POST_TYPE)) {
        		// This meta query will allow the "orderby" query var to
        		// order by event start and end timestamps.
			$additional = [
				'event_start' => [
					'key' => "event_start_timestamp",
					'type' => 'DATETIME'
				],
				'event_end' => [
					'key' => "event_end_timestamp",
					'type' => 'DATETIME'
				]
			];

			if ($dummy_q->is_main_query()) {
				$now = now_timestamptz();
				$additional['event_start']['value'] = $now;
				$additional['event_start']['compare'] = '>=';
			}

		
			// Safely insert the query into the meta_query
			$mq = phenomena_get($query_vars, "meta_query");
			$mq = $mq ? array_merge(['relation' => 'AND', $mq], $additional) : $additional;
			$query_vars['meta_query'] = $mq;
	
			// Get-and-unset the 'order' query var
			$order = phenomena_get($query_vars, 'order') ?? 'ASC';
			if (!$dummy_q->is_main_query()) {
				if (isset($query->query_vars['order'])) {
					unset($query->query_vars['order']);
				}
			}

			// create part of an orderby query that sorts by both start
			// and end date. This keeps long-duration events higher up
			// in the archive, but not quite at the top.
			$addl_ob = [
				'event_start' => $order,
				'event_end' => $order === 'DESC' ? 'ASC' : 'DESC'
			];
			// Gracefully insert it, respecting other orderby's.
			$ob = phenomena_get($query_vars, 'orderby');
	    		$query_vars['orderby'] = $ob ? array_merge($ob, $addl_ob) : $addl_ob;
		}
		return $query_vars;
	});

    // Attempt to put the event date into most WordPress themes without modification.
    add_filter('get_the_date', function($the_date, $d, $post) {
        $post_type = is_int($post) ? get_post_type($post) : $post->post_type;
        if ($post_type === PHENOMENA_POST_TYPE) {
            $s = phenomena_get_start_date($post);
            $e = phenomena_get_end_date($post);
	    if ($s && $e) {
		return $s->format($d) . ' - ' . $e->format($d);
                //return date(date($d . ' - ', $s) . $d, $e);
            } else if ($e && !$s) {
                return 'Ends ' . $e->format($d);//date($d, $e);
	    } else if ($s && !$e) {
		return $s->format($d);
            }
            return date($d, phenomena_get_start_date($post));
        }
        return $the_date;
    }, 10, 3);
} else {
	// implements ordering by event_{start,end}_timestamp meta
    // field as a query var. This is the desired behavior for the Admin
    // posts listing.
	add_action('request', function($query_vars) {
    	// appends a clause (passed as $addition) to $query_vars in a way that
    	// doesn't destroy/replace the original meta query (thus ensuring compat with
    	// most other plugins).
		function _phenomena_append_meta_query(&$query_vars, $addition, $relation = 'AND') {
			$clause_name = phenomena_guid();
			$mq = phenomena_get($query_vars, 'meta_query');
			$mq = $mq ? ['relation' => $relation, $mq, $clause_name => $addition] : [$clause_name => $addition];
			$query_vars['meta_query'] = $mq;
			return $clause_name;
		}

	    $orderby = phenomena_get($query_vars, 'orderby');
	    if ($orderby && in_array($orderby, ['event_start_timestamp', 'event_end_timestamp'])) {
			_phenomena_append_meta_query($query_vars, [
			    "meta_key" => $orderby,
			    "orderby" => 'meta_value'
			], "AND");
			unset($query_vars['orderby']);
	    }
	    return $query_vars;
	});
	
    (function() {
    	$bn = basename(__FILE__);
		$box_key = 'phenomena_event_metadata';
    	
		add_action('save_post', function($post_id) use ($bn, $box_key) {
		    if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) return;
		
		    global $post_data;
		    if ($post_data) $data = $post_data;
		    else if ($_POST) $data = $_POST;
		    else if ($_GET) $data = $_GET;
    	
			if (current_user_can('edit_post', $post_id)
				&& PHENOMENA_POST_TYPE === get_post_type($post_id)
				&& isset($data[$box_key . "_nonce"])
				&& wp_verify_nonce($data[$box_key . "_nonce"], $bn)) {
				
				foreach ([
					'event_location_name',
					'event_city',
					'event_state',
					'event_country',
					'event_street',
					'event_zip',
					'event_more_info_url'] as $key) {
					update_post_meta($post_id, $key, phenomena_get($data, $key));
				}
		
				foreach (['event_start_timestamp',
					'event_end_timestamp'] as $key) {
					update_post_meta($post_id, $key, parse_html5(phenomena_get($data, $key)));
				}
			}
		});
    	
    	add_action('add_meta_boxes', function() use ($box_key, $bn) {
    		add_meta_box($box_key, "Event Details", function() use ($bn, $box_key) {
    			function render_text_field($name, $value, $label, $type='text') {
    			?>
    			<div style="display: flex; flex-flow: column nowrap; align-items: flex-start;">
    			    <div style="padding-bottom: 5px"><?= $label; ?></div>
    			    <input type="<?= $type; ?>" name="<?= $name; ?>" value="<?= $value; ?>">
    			</div>
    			<?php
    			}
    	
    	        wp_nonce_field($bn, $box_key . '_nonce');
    	
    	        global $post;
    			
    			$post_id = $post->ID;
    			$start = get_post_meta($post_id, 'event_start_timestamp', true);
    			$end = get_post_meta($post_id, 'event_end_timestamp', true);
    			$city = get_post_meta($post_id, 'event_city', true);
    			$state = get_post_meta($post_id, 'event_state', true);
    			$country = get_post_meta($post_id, 'event_country', true);
    			$street = get_post_meta($post_id, 'event_street', true);
    			$zip = get_post_meta($post_id, 'event_zip', true);
    			$loc_name = get_post_meta($post_id, 'event_location_name', true);
    			render_text_field("event_start_timestamp", parse_utc($start), 'Start', 'datetime-local');
    	        ?><br /><?php
    			render_text_field("event_end_timestamp", parse_utc($end), 'End', 'datetime-local');
    	        ?><br /><?php
    			render_text_field("event_location_name", $loc_name, 'Location Name', 'text');
    	        ?><br /><?php
    			render_text_field("event_street", $street, 'Street', 'text');
    	        ?><br /><?php
    			render_text_field("event_city", $city, 'City', 'text');
    	        ?><br /><?php
    			render_text_field("event_state", $state, 'State', 'text');
    	        ?><br /><?php
    			render_text_field("event_zip", $zip, 'Zip', 'text');
    	        ?><br /><?php
    			render_text_field("event_country", $country, 'Country', 'text');
    	        ?><br /><?php
    			render_text_field('event_more_info_url', get_post_meta($post_id, 'event_more_info_url', true), 'More Info URL', 'text');
    		}, PHENOMENA_POST_TYPE, 'side', 'core');
    	});
    })();

    add_filter('manage_' . PHENOMENA_POST_TYPE . '_posts_columns', function($columns) {
        $columns['event_start_timestamp'] = 'Starts';
        $columns['event_end_timestamp'] = 'Ends';
        return $columns;
    });
    
    add_filter('manage_edit-' . PHENOMENA_POST_TYPE . '_sortable_columns', function($columns) {
        $columns['event_start_timestamp'] = 'event_start_timestamp';
        $columns['event_end_timestamp'] = 'event_end_timestamp';
        return $columns;
    });
    
    add_action('manage_' . PHENOMENA_POST_TYPE . '_posts_custom_column', function($column_name, $post_id) {
        if ($column_name === 'event_start_timestamp' || $column_name === 'event_end_timestamp') {
            $datetime_format = get_option('date_format') . ', ' . get_option('time_format');
            $ord = parse_utc(get_post_meta($post_id, $column_name, true), $datetime_format);
            ?><div><?= $ord; ?></div><?php
        }
    }, 10, 2);
}

