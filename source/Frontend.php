<?php

namespace ic\Plugin\EventManager;

use ic\Framework\Html\Tag;
use ic\Framework\Plugin\PluginClass;
use ic\Framework\Support\Date;
use WP_Query;

/**
 * Class Frontend
 *
 * @package ic\Plugin\EventManager
 *
 * @method EventManager getPlugin
 */
class Frontend extends PluginClass
{

	/**
	 * @inheritdoc
	 */
	protected function configure(): void
	{
		parent::configure();

		$this->hook()
		     ->on('pre_get_posts', 'filterEvents')
		     ->on('wp_head', 'addFeedLink');
	}

	/**
	 * Adds a link in the element <head> for the calendary feed.
	 */
	protected function addFeedLink(): void
	{
		if ($this->getOption('calendar.enable')) {
			echo Tag::link([
				'rel'   => 'alternate',
				'type'  => 'text/calendar',
				'title' => __('Subscribe to events — iCalendar', $this->id()),
				'href'  => get_feed_link(EventManager::FEED_TYPE),
			]);
		}
	}

	/**
	 * Filters the WP_Query object when retrieving events.
	 *
	 * @param WP_Query $query
	 */
	protected function filterEvents(WP_Query $query): void
	{
		if (empty($query->query) || !isset($query->query['post_type']) || ($query->query['post_type'] !== EventManager::POST_TYPE) || isset($query->query[EventManager::QUERY_ALL]) || $query->is_main_query()) {
			return;
		}

		$query->set('order', 'ASC');
		$query->set('meta_key', 'date_end');
		$query->set('meta_compare', '>=');
		$query->set('meta_value', Date::now()->format('Ymd'));
	}

}