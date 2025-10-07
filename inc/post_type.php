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

// Allow users to define a different taxonomy slug
if (!defined('PHENOMENA_EVENT_CATEGORY_SLUG')) define('PHENOMENA_EVENT_CATEGORY_SLUG', 'event_category');

function _phenomena_register_inline_script($handle, $deps, $script) {
	wp_register_script(
		$handle,
		false, // no src file
		$deps,
		mt_rand(),
		true
	);
	wp_add_inline_script($handle, $script, 'after');
	wp_enqueue_script($handle);
}

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

	register_post_meta(PHENOMENA_POST_TYPE, 'event_tickets_url', [
		'type' => 'string',
		'single' => true,
		'show_in_rest' => true,
		'sanitize_callback' => 'esc_url_raw',
		'auth_callback' => fn() => current_user_can('edit_posts'),
		'default' => '',
	]);

	foreach (['event_start_timestamp', 'event_end_timestamp'] as $key) {
		register_post_meta(PHENOMENA_POST_TYPE, $key, [
			'type' => 'string',
			'single' => true,

			// takes ISO8601 and converts it back to internal format
			'sanitize_callback' => function($value) {
				$dt = phenomena_parse_iso8601_date($value);
				return phenomena_format_internal($dt);
			},
			'show_in_rest' => [
				// Renders internal time (utc) to ISO8601 with timezone
				'prepare_callback' => function($value) {
					$dt = phenomena_parse_internal_date($value);
					return phenomena_format_iso8601($dt);
				}
			],
			'auth_callback' => fn() => current_user_can('edit_posts'),
			'default' => '',
		]);
	}

	foreach (['event_location_name', 'event_city', 'event_state', 'event_country', 'event_street', 'event_zip'] as $key) {
		register_post_meta(PHENOMENA_POST_TYPE, $key, [
			'type' => 'string',
			'single' => true,
			'auth_callback' => fn() => current_user_can('edit_posts'),
			'default' => '',
			'show_in_rest' => true,
		]);
	}

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

	$supports = [
			'inserter' => true,
			'align' => [
				'wide',
				'full'
			],
			'spacing' => [
				'margin' => true,
				'padding' => true
			],
			'typography' => [
				'fontFamily' => true,
				'fontSize' => true,
      				"lineHeight" => true,
				"letterSpacing" => true,
				"textDecoration" => true,
				"textTransform" => true,
				"fontStyle" => true,
				"fontWeight" => true,
				"__experimentalFontFamily" => true,
				"__experimentalFontStyle" => true,
				"__experimentalFontWeight" => true,
				"__experimentalLetterSpacing" => true,
				"__experimentalTextDecoration" => true,
				"__experimentalTextTransform" => true,
				"__experimentalWritingMode" => true
			],
			'color' => true,
			'html' => false
		];

	_phenomena_register_inline_script(
		'phenomena-block-event-timing',
		['wp-blocks', 'wp-element', 'wp-block-editor'], <<<'JS'
(function({blocks, blockEditor, element}) {
	const { createElement: el, Fragment } = element;
	const { useBlockProps, InspectorControls } = blockEditor;

	function Edit({ attributes, setAttributes }) {
		const blockProps = useBlockProps({ className: 'phenomena-block-event-timing' });

		return el(Fragment, null, ...[
			el(InspectorControls),
			el('div', { ...blockProps }, ...[
				el('h2', {}, "Event Timing")
			])
		]);
	}

	blocks.registerBlockType('phenomena/event-timing', {
		edit: Edit,
		save: () => null // dynamic block
	});
})(window.wp);
JS);

	register_block_type('phenomena/event-timing', [
		'api_version' => 3,
		'title' => "Event Timing",
		'category' => 'text',
		'icon' => 'admin-site',
		'supports' => $supports,
		'attributes' => [
			'event_start_timestamp' => [
				'type'   => 'string',
				'source' => 'meta',
				'meta'   => 'event_start_timestamp',
			],
			'event_end_timestamp' => [
				'type'   => 'string',
				'source' => 'meta',
				'meta'   => 'event_end_timestamp',
			],
		],
		'editor_script_handles' => [
			'phenomena-block-event-timing'
		],
		'render_callback' => function($attributes, $content) {
			global $post;
			$start = phenomena_get_start_date($post);
			$end = phenomena_get_end_date($post);
			
			$date_format = get_option('date_format') . ' ' . get_option('time_format');

			$timing = '';

			if ($start && $end) {
				$is_one_day = $start->format('Y-m-d') === $end->format('Y-m-d');
				if ($is_one_day) {
					$timing = $start->format(get_option('date_format')) . ' // ' . $start->format(get_option('time_format')) . ' - ' . $end->format(get_option('time_format'));
				} else {
					$timing = $start->format($date_format) . ' - ' . $end->format($date_format);
				}
			} else if ($start) {
				$timing = "Starts " . $start->format($date_format);
			} else if ($end) {
				$timing = "Ends " . $end->format($date_format);
			} else {
				// noop
			}

			ob_start();
?>
	<div <?= get_block_wrapper_attributes(); ?>><?= $timing; ?></div>
<?php
			$contents = ob_get_contents();
			ob_end_clean();

			return $contents;
		} 
	]);


	_phenomena_register_inline_script(
		'phenomena-block-event-location',
		['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor'], <<<'JS'
(function({blocks, blockEditor, element, components}) {
	const { createElement: el, Fragment } = element;
	const { useBlockProps, InspectorControls } = blockEditor;
	const { PanelBody, TextControl } = components;

	function Edit({ attributes, setAttributes }) {
		const blockProps = useBlockProps({ className: 'phenomena-block-event-timing' });

		return el(Fragment, null, ...[
			el(InspectorControls),
			el('div', { ...blockProps }, ...[
				el('h2', {}, "Event Location")
			])
		]);
	}

	blocks.registerBlockType('phenomena/event-location', {
		edit: Edit,
		save: () => null // dynamic block
	});
})(window.wp);
JS);


	register_block_type('phenomena/event-location', [
		'api_version' => 3,
		'title' => "Event Location",
		'category' => 'text',
		'icon' => 'admin-site',
		'supports' => $supports,
		'attributes' => [],
		'editor_script_handles' => [
			'phenomena-block-event-location'
		],
		'render_callback' => function($attributes, $content) {
			global $post;
			$city = phenomena_get_city($post);
                        $state = phenomena_get_state($post);
                        $country = phenomena_get_country($post);
                        $street = phenomena_get_street($post);
                        $zip = phenomena_get_zip($post);
			$loc_name = phenomena_get_location_name($post);

			$address_parts = [$street, $city, $state, $country, $zip];
			$address_parts = array_filter($address_parts, fn($x) => $x && strlen($x) > 0);
			$google_maps_link = count($address_parts) > 0 ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode(join(', ', $address_parts)) : null;

			ob_start();
?>
	<div <?= get_block_wrapper_attributes(); ?>>
			<?php if ($google_maps_link) { ?>
				<a target='_blank' href="<?= $google_maps_link; ?>"><?= $loc_name ? $loc_name : 'Google Maps'; ?></a>
			<?php } else if ($loc_name) { ?>
				<span><?= $loc_name; ?></span>
			<?php } ?>
	</div>
<?php
			$contents = ob_get_contents();
			ob_end_clean();

			return $contents;
		} 
	]);

	_phenomena_register_inline_script(
		'phenomena-block-event-tickets',
		['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor'], <<<'JS'
(function({blocks, blockEditor, element, components}) {
	const { createElement: el, Fragment } = element;
	const { useBlockProps, InnerBlocks, InspectorControls } = blockEditor;
	const { PanelBody, TextControl } = components;

	function Edit({ attributes, setAttributes }) {
		const blockProps = useBlockProps({style: {display: 'block'}});

		return el(Fragment, null, ...[
			el(InspectorControls),
			el('a', { ...blockProps }, 
				el(InnerBlocks, {
					template: [['core/paragraph', {content: 'Get Tickets'}]],
					templateLock: false,
					allowedBlocks: ['core/heading', 'core/paragraph'],
					orientation: 'vertical'
				})
			)
		]);
	}

	blocks.registerBlockType('phenomena/event-tickets', {
		edit: Edit,
		save: () => el(InnerBlocks.Content, {}) // dynamic block
	});
})(window.wp);
JS);

	register_block_type('phenomena/event-tickets', [
		'api_version' => 3,
		'title' => "Event Tickets Link",
		'category' => 'text',
		'icon' => 'admin-site',
		'supports' => $supports,
		'attributes' => [
			'event_tickets_url' => [
				'type'   => 'string',
				'source' => 'meta',
				'meta'   => 'event_tickets_url',
			],
		],
		'template' => [
			['core/paragraph', [
				'placeholder' => 'Link Text Here...'
			]],
		],
		'template_lock' => 'all', // or 'insert' or false
		'editor_script_handles' => [
			'phenomena-block-event-tickets'
		],
		'render_callback' => function($attributes, $content) {
			global $post;
			$url = phenomena_get_tickets_url($post);

			ob_start();
			if ($url) {
?>
	<a <?= get_block_wrapper_attributes(["style" => "display: inline-block;"]); ?> href="<?= $url; ?>" target="_blank"><?= do_blocks($content); ?></a>
<?php
			}
			$contents = ob_get_contents();
			ob_end_clean();

			return $contents;
		} 
	]);

});

add_action('enqueue_block_editor_assets', function() {
		_phenomena_register_inline_script('phenomena_event_meta', ['wp-plugins','wp-edit-post','wp-components','wp-data','wp-core-data','wp-element'], <<<'JS'
(function() {
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel, store: editorStore } = wp.editor;
	const { DateTimePicker, TextControl, BaseControl } = wp.components;
	const { useSelect } = wp.data;
	const { useEntityProp, store: coreStore } = wp.coreData;
	const { createElement: h, useMemo, Fragment } = wp.element;
	const { format: formatDate, dateI18n } = wp.date;

	const selectPostType = select => select(editorStore).getCurrentPostType();

	const useMetaKey = (key, dflt = undefined) => {
		const postType = useSelect(selectPostType, []);
		const [meta, setMeta] = useEntityProp('postType', postType, 'meta');

		return [meta[key] || dflt, v => setMeta({[key]: v})];
	};

	const Panel = () => {
		const postType = useSelect(selectPostType, []);
		if (postType !== 'event') return null;

		const [startTimestamp, setStartTimestamp] = useMetaKey('event_start_timestamp');
		const [endTimestamp, setEndTimestamp] = useMetaKey('event_end_timestamp');
		const [locName, setLocName] = useMetaKey('event_location_name');
		const [locCity, setLocCity] = useMetaKey('event_city');
		const [locState, setLocState] = useMetaKey('event_state');
		const [locCountry, setLocCountry] = useMetaKey('event_country');
		const [locStreet, setLocStreet] = useMetaKey('event_street');
		const [locZip, setLocZip] = useMetaKey('event_zip');
		const [ticketUrl, setTicketUrl] = useMetaKey('event_tickets_url', '');

		const urlInvalid = useMemo(() => {
			if (!ticketUrl) return false;
			try {
				const u = new URL(ticketUrl);
				return !(u.protocol === 'http:' || u.protocol === 'https:');
			} catch (e) { return true; }
    		}, [ticketUrl]);

		return h(
			Fragment,
			{},
			h(
				PluginDocumentSettingPanel,
    				{name: 'phenomena-event-starts', title: 'Event Starts'},
				h(DateTimePicker, {
					currentDate: startTimestamp,
					onChange: v => setStartTimestamp(formatDate('c', v)),
					is12Hour: true
				})
			),
			h(
				PluginDocumentSettingPanel,
    				{name: 'phenomena-event-ends', title: 'Event Ends'},

				h(DateTimePicker, {
					currentDate: endTimestamp,
					onChange: v => setEndTimestamp(formatDate('c', v)),
					is12Hour: true
				})
			),
			h(
				PluginDocumentSettingPanel,
    				{name: 'phenomena-event-ticketing', title: 'Event Ticketing'},

				h(TextControl, {
					label: "Ticket URL",
					type: 'url',
					value: ticketUrl,
					onChange: v => setTicketUrl(v),
					placeholder: 'https://example.com/buy',
					help: urlInvalid
						? 'Please enter a valid http(s) URL.'
						: 'Public link customers use to purchase tickets.'
				}),
			),
			h(
				PluginDocumentSettingPanel,
    				{name: 'phenomena-event-location', title: 'Event Location'},

				h(TextControl, {
					label: "Name",
					type: 'string',
					value: locName,
					onChange: v => setLocName(v),
					placeholder: 'e.g. Carnegie Hall',
					help: "Name of the venue hosting the event"
				}),
				h(TextControl, {
					label: "City",
					type: 'string',
					value: locCity,
					onChange: v => setLocCity(v),
					placeholder: 'e.g. New York City'
				}),
				h(TextControl, {
					label: "State / Locality",
					type: 'string',
					value: locState,
					onChange: v => setLocState(v),
					placeholder: 'e.g. NJ',
				}),
				h(TextControl, {
					label: "Street Address",
					type: 'string',
					value: locStreet,
					onChange: v => setLocStreet(v),
					placeholder: 'e.g. 123 Example Dr.',
				}),
				h(TextControl, {
					label: "ZIP/Postal Code",
					type: 'string',
					value: locZip,
					onChange: v => setLocZip(v),
					placeholder: 'e.g. 08108',
				}),
				h(TextControl, {
					label: "Country",
					type: 'string',
					value: locCountry,
					onChange: v => setLocCountry(v),
					placeholder: 'e.g. United States',
				}),
			)
		);
	};

	registerPlugin('phenomena', { render: Panel });
})();
JS);
	});



if (!is_admin()) {
	// The non-admin implementation of event ordering.
	// This is designed to make events appear in a
	// chronological order that makes intuitive sense.
	add_action('request', function($query_vars) {
		$pt = phenomena_get($query_vars, 'post_type', []);

		// Support Polyphony
		if (is_array($pt)) {
			$pt = count($pt) === 0 ? null : $pt[0];
		}

		$is_archive = $pt === PHENOMENA_POST_TYPE && !isset($query_vars[PHENOMENA_POST_TYPE]);
		if ($is_archive) {
        		// This meta query will allow the "orderby" query var to
        		// order by event start and end timestamps.
			$additional = [
				'relation' => 'OR',
				'event_start' => [
					'key' => "event_start_timestamp",
					'type' => 'DATETIME',
					'value' => now_timestamptz(),
					'compare' => '>='
				],
				'event_end' => [
					'key' => "event_end_timestamp",
					'type' => 'DATETIME',
					'value' => now_timestamptz(),
					'compare' => '>='
				],
			];

			// Safely insert the query into the meta_query
			$mq = phenomena_get($query_vars, "meta_query");
			if ($mq) {
				$additional['original_meta_query'] = $mq;
			}
			$query_vars['meta_query'] = $additional;
	
			// Get-and-unset the 'order' query var
			$order = phenomena_get($query_vars, 'order') ?? 'ASC';
			if (isset($query_vars['order'])) {
				unset($query_vars['order']);
			}

			$ob = phenomena_get($query_vars, 'orderby', []);
			$ob['event_start'] = $order;
			$ob['event_end'] = $order === 'DESC' ? 'ASC' : 'DESC';
			$query_vars['orderby'] = $ob;
	 
		}
		return $query_vars;
    });
} else {
	// implements ordering by event_{start,end}_timestamp meta
	// field as a query var. This is the desired behavior for the Admin
	// posts listing.
	add_action('request', function($query_vars) {
		$orderby = phenomena_get($query_vars, 'orderby');
		if ($orderby && in_array($orderby, ['event_start_timestamp', 'event_end_timestamp'])) {
			$additional = [[
				"meta_key" => $orderby,
				"meta_type" => "DATETIME",
				"orderby" => 'meta_value'
			]];

			// Safely insert the query into the meta_query
			$mq = phenomena_get($query_vars, "meta_query");
			if ($mq) {
				$additional['original_meta_query'] = $mq;
			}
			$query_vars['meta_query'] = $additional;
			unset($query_vars['orderby']);
		}
		return $query_vars;
	});

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
			$dt = phenomena_parse_internal_date(phenomena_get_post_meta($post_id, $column_name));
			$ord = $dt ? $dt->format($datetime_format) : '';
			?><div><?= $ord; ?></div><?php
		}
	}, 10, 2);
}

