<?php

namespace ic\Plugin\EventManager;

use ic\Framework\Plugin\PluginClassDecorator;
use ic\Framework\Support\Arr;
use ic\Framework\Support\Date;
use ic\Framework\Support\Str;

/**
 * Class Events
 *
 * @package ic\Plugin\EventManager
 */
class Events
{

	use PluginClassDecorator;

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
		$this->setPlugin($plugin);
	}

	/**
	 * @return array
	 */
	public function calendar(): array
	{
		$name     = get_bloginfo('name');
		$language = explode('-', get_bloginfo('language'));
		$filename = urlencode(strtolower($name . '-' . reset($language)) . '.ics');
		$contents = $this->format($this->serialize([
			'BEGIN'        => 'VCALENDAR',
			'PRODID'       => sprintf('-//%s/Calendar %s//EN', $this->name(), $this->version()),
			'VERSION'      => '2.0',
			'CALSCALE'     => 'GREGORIAN',
			'METHOD'       => 'PUBLISH',
			'X-WR-CALNAME' => $this->getOption('calendar.name') ? $name : '',
			0              => implode('', $this->events($this->getOption('calendar.limit'))),
			'END'          => 'VCALENDAR',
		]));

		return compact('filename', 'contents');
	}

	/**
	 * @param int  $limit
	 * @param bool $past
	 *
	 * @return Event[]
	 */
	public function events(int $limit = 0, bool $past = false): array
	{
		$query  = $this->query($limit, $past);
		$events = [];

		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();

				$events[] = $this->getEvent(get_post());
			}
		}

		$events = $this->hook()->apply('ic_event_manager_events', $events);

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
				'meta_value'   => Date::now()->format('Ymd'),
			]);
		}

		return new \WP_Query($arguments);
	}

	/**
	 * @param \WP_Post $post
	 *
	 * @return Event
	 */
	protected function getEvent(\WP_Post $post): ?Event
	{
		$event = [
			'uid'         => $post->ID,
			'uri'         => wp_get_shortlink($post),
			'description' => get_the_title($post),
			'summary'     => $this->getSummary($post),
			'organizer'   => $this->getOrganizer($post),
			'image'       => $this->getImage($post, $this->getOption('calendar.image')),
		];

		$fields = get_fields($post->ID);

		if (empty($fields)) {
			return null;
		}

		return new Event($this, $event, $fields);

	}

	/**
	 * @param \WP_Post $post
	 *
	 * @return string
	 */
	protected function getSummary(\WP_Post $post): string
	{
		$summary = strip_shortcodes($post->post_content);
		$summary = apply_filters('the_content', $summary);
		$summary = strip_tags($summary);

		return $summary;
	}

	/**
	 * @param \WP_Post $post
	 *
	 * @return string
	 */
	protected function getOrganizer(\WP_Post $post): string
	{
		if ($this->getOption('organizer.enable')) {
			$terms = get_the_terms($post, EventManager::TAX_TYPE);

			if (\is_array($terms)) {
				return $terms[0]->name;
			}

			return $this->getOption('organizer.default');
		}

		return '';
	}

	/**
	 * @param \WP_Post $post
	 * @param string   $size
	 *
	 * @return string
	 */
	protected function getImage(\WP_Post $post, string $size): string
	{
		$id = get_post_meta($post->ID, '_thumbnail_id', true);

		if (!$id) {
			return '';
		}

		$image = wp_get_attachment_image_src($id, $size);

		if (!$image) {
			return '';
		}

		return $image[0];
	}

	/**
	 * @param array $input
	 *
	 * @return string
	 */
	public function serialize(array $input): string
	{
		$serialized = Arr::map($input, function ($key, $value) {
			if (empty($value)) {
				return '';
			}

			if (is_numeric($key)) {
				return $value;
			}

			return "$key:$value\r\n";
		});

		return implode('', $serialized);
	}

	/**
	 * @param string $input
	 * @param int    $length
	 *
	 * @return string
	 */
	protected function format(string $input, int $length = 74): string
	{
		$output   = '';
		$position = 0;

		while ($position < Str::length($input)) {
			// find the newline
			$newline = Str::search($input, "\n", $position + 1);

			if (!$newline) {
				$newline = Str::length($input);
			}

			$line = Str::substring($input, $position, $newline - $position);

			if (Str::length($line) <= $length) {
				$output .= $line;
			} else {
				// First line cut-off limit is $lineLimit
				$output .= Str::substring($line, 0, $length);
				$line   = Str::substring($line, $length);

				// Subsequent line cut-off limit is $lineLimit - 1 due to the leading white space
				$output .= "\n " . Str::substring($line, 0, $length - 1);

				while (Str::length($line) > $length - 1) {
					$line   = Str::substring($line, $length - 1);
					$output .= "\n " . Str::substring($line, 0, $length - 1);
				}
			}

			$position = $newline;
		}

		return $output;
	}

}
