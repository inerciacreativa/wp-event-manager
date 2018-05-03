<?php

namespace ic\Plugin\EventManager;

/**
 * @param int  $limit
 * @param bool $past
 *
 * @return array
 */
function events(int $limit = 0, bool $past = false): array
{
	return EventManager::instance()->getEvents($limit, $past);
}