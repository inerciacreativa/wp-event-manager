<?php

namespace ic\Plugin\EventManager;

use ic\Framework\Hook\HookDecorator;
use ic\Framework\Support\Date;
use ic\Framework\Support\Options;

/**
 * Class Events
 *
 * @package ic\Plugin\EventManager
 */
class Events
{

	use HookDecorator;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @param EventManager $plugin
	 *
	 * @return static
	 *
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public static function create(EventManager $plugin): Events
	{
		return new static($plugin);
	}

	/**
	 * Calendar constructor.
	 *
	 * @param EventManager $plugin
	 *
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function __construct(EventManager $plugin)
	{
		$this->options = $plugin->getOptions();
	}

	/**
	 * @param string $image
	 * @param int    $limit
	 * @param bool   $past
	 *
	 * @return Event[]
	 */
	public function events(string $image = 'thumbnail', int $limit = 0, bool $past = false): array
	{
		$query  = $this->query($limit, $past);
		$events = [];

		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();

				$events[] = $this->event(get_post(), $image);
			}
		}

		$events = $this->hook()
		               ->apply('ic_event_manager_events', $events);

		return $events;
	}

	/**
	 * @param int  $limit
	 * @param bool $past
	 *
	 * @return \WP_Query
	 */
	public function query(int $limit = 0, bool $past = false): \WP_Query
	{
		$arguments = [
			'post_type'      => EventManager::POST_TYPE,
			'posts_per_page' => $limit ?: -1,
			'order'          => 'DESC',
		];

		if (!$past) {
			$arguments = array_merge($arguments, [
				'meta_key'     => 'date_end',
				'meta_compare' => '>=',
				'meta_value'   => Date::now()
				                      ->format('Ymd'),
			]);
		} else {
			$arguments[EventManager::QUERY_ALL] = true;
		}

		return new \WP_Query($arguments);
	}

	/**
	 * @param \WP_Post $post
	 * @param string   $image
	 *
	 * @return Event
	 */
	public function event(\WP_Post $post, string $image): ?Event
	{
		return new Event($post, $image, $this->options);
	}

}
