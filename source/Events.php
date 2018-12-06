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
	 * @param array  $arguments
	 *
	 * @return Event[]
	 */
	public function events(string $image = 'thumbnail', array $arguments = []): array
	{
		$query  = $this->query($arguments);
		$events = [];

		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();

				$events[] = $this->event(get_post(), $image);
			}
		}

		$events = $this->hook()->apply('ic_event_manager_events', $events);

		return $events;
	}

	/**
	 * @param array $arguments
	 *
	 * @return \WP_Query
	 */
	public function query(array $arguments = []): \WP_Query
	{
		$arguments = array_merge([
			'posts_per_page' => -1,
			'order'          => 'DESC',
		], $arguments, [
			'post_type' => EventManager::POST_TYPE,
		]);

		if (empty($arguments['no_date_filter']) && empty($arguments['meta_key'])) {
			$arguments = array_merge($arguments, [
				'meta_key'     => 'date_end',
				'meta_compare' => '>=',
				'meta_value'   => Date::now()->format('Ymd'),
			]);
		} else {
			$arguments[EventManager::QUERY_ALL] = true;
		}

		unset($arguments['no_date_filter']);

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
