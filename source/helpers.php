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