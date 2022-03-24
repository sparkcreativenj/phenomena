<?php

// Generate an RFC-4122 compliant globaly unique identifier
function phenomena_guid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Returns a value from an array safely
function phenomena_get($array, $keys, $default = null) {
    if (!$array) return is_callable($default) ? $default() : $default;

    if (!is_array($keys)) {
        $keys = [$keys];
    }

    $v = $array;

    foreach ($keys as $key) {
        if (!array_key_exists($key, $v)) return is_callable($default) ? $default() : $default;
        $v = $v[$key];
        if (empty($v)) return is_callable($default) ? $default() : $default;
    }

    return $v;
}

// Run a query for its posts
function phenomena_get_posts($query_vars) {
	return (new WP_Query())->query($query_vars);
}

// Gets a meta field by key
function phenomena_get_post_meta($post, $key) {
    return get_post_meta(is_int($post) ? $post : $post->ID, $key, true);
}

