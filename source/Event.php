<?php

namespace ic\Plugin\EventManager;

use ic\Framework\Support\Date;
use ic\Framework\Support\Options;
use ic\Framework\Support\Str;
use ic\Framework\Support\TextLimiter;

/**
 * Class Event
 *
 * @package ic\Plugin\EventManager
 *
 * @property-read \WP_Post    $post
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
 * @property-read array       $location
 * @property-read string      $organizer
 * @property-read string      $web
 *
 */
class Event
{

	/**
	 * @var \WP_Post
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
	 * @var \DateTime
	 */
	private $dateStamp;

	/**
	 * @var \DateTime
	 */
	private $startDate;

	/**
	 * @var \DateTime
	 */
	private $endDate;

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
	 * @param \WP_Post $post
	 * @param string   $image
	 * @param Options  $options
	 */
	public function __construct(\WP_Post $post, string $image, Options $options)
	{
		if ($post->post_type !== EventManager::POST_TYPE) {
			throw new \InvalidArgumentException(sprintf('The post has an incorrect post type "%s" (ID %d).', $post->post_type, $post->ID));
		}

		$event = get_fields($post->ID);

		if (empty($event)) {
			throw new \RuntimeException(sprintf('There are no event information for the post (ID %d).', $post->ID));
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
		$this->location    = self::getLocation($event);
		$this->summary     = self::getSummary($post);
		$this->organizer   = self::getOrganizer($post, $options);
		$this->image       = self::getImage($post, $image);
		$this->web         = $event['url'];
	}

	/**
	 * @param string $name
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return int|string|\DateTime
	 */
	public function __get($name)
	{
		if (property_exists($this, $name)) {
			return $this->$name;
		}

		throw new \InvalidArgumentException(sprintf('%s does not exists.', $name));
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value)
	{
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		return property_exists($this, $name);
	}

	/**
	 * @param array $event
	 *
	 * @return Date
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
	 * @param \WP_Post $post
	 *
	 * @return string
	 */
	protected static function getSummary(\WP_Post $post): string
	{
		$summary = strip_shortcodes($post->post_content);
		$summary = apply_filters('the_content', $summary);
		$summary = strip_tags($summary);
		$summary = trim(Str::whitespace($summary));

		if (empty($summary)) {
			return '';
		}

		$length  = Str::length($summary);
		$summary = rtrim(TextLimiter::words($summary, 60));

		if (!empty($summary) && ($length > Str::length($summary))) {
			$summary .= '…';
		}

		return $summary;
	}

	/**
	 * @param \WP_Post $post
	 * @param string   $size
	 *
	 * @return \stdClass
	 */
	protected static function getImage(\WP_Post $post, string $size): ?\stdClass
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
	 * @param \WP_Post $post
	 * @param Options  $options
	 *
	 * @return string
	 */
	protected static function getOrganizer(\WP_Post $post, Options $options): string
	{
		if ($options->get('organizer.enable')) {
			$terms = get_the_terms($post, EventManager::TAX_TYPE);

			if (\is_array($terms)) {
				return $terms[0]->name;
			}

			return $options->get('organizer.default');
		}

		return '';
	}

}