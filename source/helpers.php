<?php

namespace ic\Plugin\EventManager;

/**
 * @param string $image
 * @param array  $arguments
 *
 * @return array
 */
function events(string $image = 'thumbnail', array $arguments = []): array
{
	return EventManager::instance()
	                   ->getEvents($image, $arguments);
}

/**
 * @param \WP_Post $post
 * @param string   $image
 *
 * @return Event
 */
function event(\WP_Post $post, string $image = 'thumbnail'): Event
{
	return EventManager::instance()
	                   ->getEvent($post, $image);
}