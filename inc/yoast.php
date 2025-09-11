<?php

add_action('plugins_loaded', function() {
//if (class_exists('\Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece')) {
	add_filter('wpseo_schema_graph_pieces', function($pieces, $context) {
		if (is_singular(PHENOMENA_POST_TYPE)) {
			$pieces[] = new Event_Schema_Piece($context);
		}
		return $pieces;
	}, 11, 2);

	add_filter('wpseo_schema_needs_article', function($needs) {
		return is_singular(PHENOMENA_POST_TYPE) ? false : $needs;
	});

	class Event_Schema_Piece extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {
		public function is_needed() {
			return is_singular(PHENOMENA_POST_TYPE);
		}

		private function filter_jsonld($jsonld) {
			return array_filter($jsonld, function($v, $k) {
				if (is_string($k) && $k[0] === '@') return true;
				return $v !== null && $v !== '';
			}, ARRAY_FILTER_USE_BOTH);
		}

		public function generate() {
			global $post;
			if (!$post) return [];

			$id = get_the_ID();
			$name = get_the_title($id);

			if (!$name) return [];

			$start = phenomena_get_start_date($post);
			$end = phenomena_get_end_date($post);

			if (!$start) return [];

			$virtual_url = null; // TODO implement this functionality

			$location = null;
			$attendance_mode = null;

			$venue_name = phenomena_get_location_name($post);

			if ($virtual_url) {
				$location = $this->filter_jsonld([
					'@type' => 'VirtualLocation',
					'url' => $virtual_url,
					'name' => $venue_name
				]);

				$attendance_mode = 'https://schema.org/OnlineEventAttendanceMode';
			} else {
				$addr = $this->filter_jsonld([
					'@type' => 'PostalAddress',
					'streetAddress' => phenomena_get_street($post),
					'addressLocality' => phenomena_get_city($post),
					'addressRegion' => phenomena_get_state($post),
					'postalCode' => phenomena_get_zip($post),
					'addressCountry' => phenomena_get_country($post)
				]);

				if ($venue_name || count($addr) > 1) {
					$location = [
						'@type' => 'Place',
						'name' => $venue_name ?: null,
						'address'=> !empty($addr) ? $addr : null
					];
					$attendance_mode = 'https://schema.org/OfflineEventAttendanceMode';
				}
			}

			if (!$location) return [];

			$tickets_url = phenomena_get_tickets_url($post);
			$ticket_price = null; // TODO: implement
			$ticket_price_currency = null; // TODO: implement
			if ($ticket_price && !$ticket_price_currency) {
				$ticket_price_currency = 'USD';
			}

			$desc = get_the_excerpt($id) ?: wp_strip_all_tags(get_post_field('post_content', $id));
			$img_url = get_the_post_thumbnail_url($id, 'full');

			return [$this->filter_jsonld([
				'@type' => 'Event',
				'@id' => home_url('/#/schema/Event/' . $id),
				'name' => $name,
				'description' => $desc ?: null,
				'startDate' => $start->format('c'),
				'endDate' => $end ? $end->format('c') : null,
				'location' => $location,
				'eventAttendanceMode'=> $attendance_mode,
				'image' => $img_url ? [
					'@id' => $img_url
				] : null,
				'offers' => $tickets_url ? [
					'@type' => 'Offer',
					'url' => $tickets,
					'price' => $ticket_price,
					'ticket_price_currency' => $ticket_price_currency,
					'availability' => 'https://schema.org/InStock'
				] : null,
				'organizer' => [
					'@id' => \Yoast\WP\SEO\Config\Schema_IDs::ORGANIZATION_HASH // falls back to your Site Organization
				],
				'isPartOf' => [
					'@id' => $this->context->canonical
				],
				'mainEntityOfPage' => [
					'@id' => $this->context->canonical
				],
				'url' => get_permalink($id)
			])];
		}
	}
}, 11, 2);
