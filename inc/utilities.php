<?php

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

// Gets a meta field by key
function phenomena_get_post_meta($post, $key) {
	return get_post_meta(is_int($post) ? $post : $post->ID, $key, true);
}

