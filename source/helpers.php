<?php

namespace ic\Plugin\EventManager;

/**
 * @param string $image
 * @param int    $limit
 * @param bool   $past
 *
 * @return array
 */
function events(string $image = 'thumbnail', int $limit = 0, bool $past = false): array
{
	return EventManager::instance()->getEvents($image, $limit, $past);
}

/**
 * @param \WP_Post $post
 * @param string   $image
 *
 * @return Event
 */
function event(\WP_Post $post, string $image = 'thumbnail'): Event
{
	return EventManager::instance()->getEvent($post, $image);
}