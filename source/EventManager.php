<?php

namespace ic\Plugin\EventManager;

use ic\Framework\Plugin\Plugin;
use ic\Framework\Type\PostType;
use ic\Framework\Type\Taxonomy;
use WP_Post;

/**
 * Class EventManager
 *
 * @package ic\Plugin\EventManager
 */
class EventManager extends Plugin
{

	public const POST_TYPE = 'event';

	public const TAX_TYPE = 'event_organizer';

	public const FEED_TYPE = 'calendar';

	public const FEED_ACTION = 'download';

	public const QUERY_ALL = '_all_events_';

	/**
	 * @inheritdoc
	 */
	protected function dependencies()
	{
		if (!function_exists('register_field_group')) {
			return __('This plugin requires <a href="https://wordpress.org/plugins/advanced-custom-fields/" target="_blank">Advanced Custom Fields</a>. Please, install and activate ACF before activating this plugin.', $this->id());
		}

		return true;
	}

	/**
	 * @inheritdoc
	 */
	protected function install(): void
	{
		// Convert the old taxonomy name.
		global $wpdb;

		/** @noinspection SqlResolve */
		$taxonomy = $wpdb->get_var($wpdb->prepare("SELECT taxonomy FROM $wpdb->term_taxonomy WHERE taxonomy LIKE %s", 'event_%%'));

		if ($taxonomy !== self::TAX_TYPE) {
			$wpdb->update($wpdb->term_taxonomy, ['taxonomy' => self::TAX_TYPE], ['taxonomy' => $taxonomy]);
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function configure(): void
	{
		parent::configure();

		$this->setOptions([
			'acf'       => false,
			'slug'      => [
				'event'     => 'events',
				'organizer' => 'organizers',
			],
			'organizer' => [
				'enable'  => true,
				'default' => get_bloginfo('name'),
			],
			'calendar'  => [
				'enable' => false,
				'name'   => true,
				'limit'  => 0,
				'image'  => 'medium',
			],
		]);
	}

	/**
	 * @inheritdoc
	 */
	protected function initialize(): void
	{
		CustomFields::register($this);

		PostType::create(self::POST_TYPE)
		        ->nouns(__('Event', $this->id()), __('Events', $this->id()))
		        ->rewrite($this->getOption('slug.event'), false)
		        ->filter_link([$this, 'getEventLink'])
		        ->menu('dashicons-calendar')
		        ->supports([
			        'title',
			        'editor',
			        'thumbnail',
			        'comments',
			        'author',
		        ]);

		if ($this->getOption('organizer.enable')) {
			Taxonomy::create(self::TAX_TYPE, [self::POST_TYPE])
			        ->nouns(__('Organizer', $this->id()), __('Organizers', $this->id()))
			        ->rewrite($this->getOption('slug.organizer'), false)
			        ->meta_box(false, false);
		}

		if ($this->getOption('calendar.enable')) {
			add_feed(self::FEED_TYPE, [$this, 'getCalendar']);

			$this->hook()->on('template_redirect', 'getEventCalendar');
		}
	}

	/**
	 * @param string  $link
	 * @param WP_Post $post
	 *
	 * @return string
	 */
	public function getEventLink(string $link, WP_Post $post): string
	{
		if ($date = $this->getEventDate($post)) {
			$search  = $this->getOption('slug.event');
			$replace = sprintf('%s/%d/%d/%d', $search, $date['year'], $date['month'], $date['day']);
			$link    = str_replace($search, $replace, $link);
		}

		return $link;
	}

	/**
	 * @param WP_Post $post
	 *
	 * @return array|null
	 */
	public function getEventDate(WP_Post $post): ?array
	{
		if ($post->post_type !== self::POST_TYPE) {
			return null;
		}

		if ($date = get_field('date_start', $post->ID)) {
			return [
				'year'  => substr($date, 0, 4),
				'month' => substr($date, 4, 2),
				'day'   => substr($date, 6, 2),
			];
		}

		return null;
	}

	/**
	 * @param string $image
	 * @param array  $arguments
	 *
	 * @return array
	 */
	public function getEvents(string $image = 'thumbnail', array $arguments = []): array
	{
		return Events::create($this)->events($image, $arguments);
	}

	/**
	 * @param WP_Post $post
	 * @param string  $image
	 *
	 * @return Event
	 */
	public function getEvent(WP_Post $post, string $image = 'thumbnail'): Event
	{
		return Events::create($this)->event($post, $image);
	}

	/**
	 * Retrieves the .ics file.
	 */
	public function getCalendar(): void
	{
		$events = Events::create($this)
		                ->events($this->getOption('calendar.image'), $this->getOption('calendar.limit'));

		Calendar::create($this)->addEvents($events)->send();
	}

	/**
	 *
	 */
	protected function getEventCalendar(): void
	{
		if (empty($_GET[self::FEED_ACTION]) || ($_GET[self::FEED_ACTION] !== self::FEED_TYPE) || !is_singular(self::POST_TYPE)) {
			return;
		}

		Calendar::create($this)->addPost(get_post())->send();
	}

}
