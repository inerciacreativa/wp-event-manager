<?php

namespace ic\Plugin\EventManager;

use DateTime;
use ic\Framework\Data\Options;
use ic\Framework\Support\Date;
use ic\Framework\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use WP_Post;

/**
 * Class Event
 *
 * @package ic\Plugin\EventManager
 *
 * @property-read WP_Post     $post
 * @property-read int         $uid
 * @property-read string      $name
 * @property-read string      $uri
 * @property-read string      $link
 * @property-read string      $description
 * @property-read string      $summary
 * @property-read object|null $image
 * @property-read Date        $dateStamp
 * @property-read Date        $startDate
 * @property-read Date        $endDate
 * @property-read int         $days
 * @property-read bool        $allDay
 * @property-read bool        $isActive
 * @property-read array       $location
 * @property-read string      $organizer
 * @property-read string      $web
 *
 */
class Event
{

	/**
	 * @var WP_Post
	 */
	private $post;

	/**
	 * @var int
	 */
	private $uid;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $uri;

	/**
	 * @var string
	 */
	private $link;

	/**
	 * @var string
	 */
	private $description;

	/**
	 * @var string
	 */
	private $summary;

	/**
	 * @var string
	 */
	private $image;

	/**
	 * @var DateTime
	 */
	private $dateStamp;

	/**
	 * @var DateTime
	 */
	private $startDate;

	/**
	 * @var DateTime
	 */
	private $endDate;

	/**
	 * @var int
	 */
	private $days;

	/**
	 * @var bool
	 */
	private $allDay;

	/**
	 * @var bool
	 */
	private $isActive;

	/**
	 * @var array
	 */
	private $location;

	/**
	 * @var string
	 */
	private $organizer;

	/**
	 * @var string
	 */
	private $web;

	/**
	 * CalendarEvent constructor.
	 *
	 * @param WP_Post $post
	 * @param string  $image
	 * @param Options $options
	 *
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function __construct(WP_Post $post, string $image, Options $options)
	{
		if ($post->post_type !== EventManager::POST_TYPE) {
			throw new InvalidArgumentException(sprintf('The post has an incorrect post type "%s" (ID %d).', $post->post_type, $post->ID));
		}

		$event = get_fields($post->ID);

		if (empty($event)) {
			throw new RuntimeException(sprintf('There are no event information for the post (ID %d).', $post->ID));
		}

		$this->post        = $post;
		$this->uid         = $post->ID;
		$this->name        = $post->post_name;
		$this->uri         = home_url('?p=' . $post->ID);
		$this->link        = get_permalink($post);
		$this->description = html_entity_decode(get_the_title($post), ENT_HTML5 | ENT_QUOTES);
		$this->dateStamp   = Date::now();
		$this->startDate   = self::getStartDate($event);
		$this->endDate     = self::getEndDate($event);
		$this->allDay      = $event['date_allday'];
		$this->days        = (int) $this->startDate->get()->diff($this->endDate->get())->days;
		$this->isActive    = $this->dateStamp->format('U') < $this->endDate->format('U');
		$this->location    = self::getLocation($event);
		$this->summary     = self::getSummary($post, $options);
		$this->organizer   = self::getOrganizer($post, $options);
		$this->image       = self::getImage($post, $image);
		$this->web         = $event['url'];
	}

	/**
	 * @param string $name
	 *
	 * @return mixed
	 *
	 * @throws InvalidArgumentException
	 */
	public function __get(string $name)
	{
		if (property_exists($this, $name)) {
			return $this->$name;
		}

		throw new InvalidArgumentException(sprintf('%s does not exists.', $name));
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function __set(string $name, $value)
	{
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function __isset(string $name)
	{
		return property_exists($this, $name);
	}

	/**
	 * @param array $event
	 *
	 * @return Date
	 *
	 * @throws InvalidArgumentException
	 */
	protected static function getStartDate(array $event): Date
	{
		$date = mysql2date('Y-m-d', $event['date_start']);
		$time = $event['date_allday'] ? '00:00:00' : sprintf('%d:%d:00', $event['time_start_hour'], $event['time_start_minutes']);

		return Date::create("$date $time");
	}

	/**
	 * @param array $event
	 *
	 * @return Date
	 *
	 * @throws InvalidArgumentException
	 */
	protected static function getEndDate(array $event): Date
	{
		if (empty($event['date_end']) || ($event['date_end'] === $event['date_start'])) {
			$date = mysql2date('Y-m-d', $event['date_start']);
			$time = $event['date_allday'] ? '23:59:59' : sprintf('%d:%d:00', $event['time_end_hour'], $event['time_end_minutes']);
		} else {
			$date = mysql2date('Y-m-d', $event['date_end']);
			$time = $event['date_allday'] ? '00:00:00' : sprintf('%d:%d:00', $event['time_end_hour'], $event['time_end_minutes']);
		}

		return Date::create("$date $time");
	}

	/**
	 * @param array $event
	 *
	 * @return array
	 */
	protected static function getLocation(array $event): array
	{
		$keys     = array_flip(['venue', 'address', 'city']);
		$location = array_intersect_key(array_replace($keys, $event), $keys);
		$location = array_filter($location);

		return $location;
	}

	/**
	 * @param WP_Post $post
	 * @param Options $options
	 *
	 * @return string
	 */
	protected static function getSummary(WP_Post $post, Options $options): string
	{
		if (!empty($post->post_excerpt)) {
			return $post->post_excerpt;
		}

		$summary = strip_shortcodes($post->post_content);
		if (function_exists('excerpt_remove_blocks')) {
			$summary = excerpt_remove_blocks($summary);
		}

		$summary = apply_filters('the_content', $summary);

		$summary = Str::stripTags($summary, ['figure']);
		$summary = Str::whitespace($summary);
		$summary = Str::words($summary, (int) $options->get('calendar.words'));

		return $summary;
	}

	/**
	 * @param WP_Post $post
	 * @param string  $size
	 *
	 * @return object|null
	 */
	protected static function getImage(WP_Post $post, string $size): ?object
	{
		$id = get_post_meta($post->ID, '_thumbnail_id', true);

		if (!$id) {
			return null;
		}

		$image = wp_get_attachment_image_src($id, $size);

		if (!$image) {
			return null;
		}

		return (object) [
			'src'    => $image[0],
			'width'  => $image[1],
			'height' => $image[2],
		];
	}

	/**
	 * @param WP_Post $post
	 * @param Options $options
	 *
	 * @return string
	 */
	protected static function getOrganizer(WP_Post $post, Options $options): string
	{
		if ($options->get('organizer.enable')) {
			$terms = get_the_terms($post, EventManager::TAX_TYPE);

			if (is_array($terms)) {
				return $terms[0]->name;
			}

			return $options->get('organizer.default');
		}

		return '';
	}

}
