<?php

namespace ic\Plugin\EventManager;

use ic\Framework\Data\Options;
use ic\Framework\Support\Arr;
use ic\Framework\Support\Str;
use WP_Post;

/**
 * Class Calendar
 *
 * @package ic\Plugin\EventManager
 */
class Calendar
{

	/**
	 * @var Event[]
	 */
	private $events = [];

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @param EventManager $plugin
	 *
	 * @return static
	 */
	public static function create(EventManager $plugin): Calendar
	{
		return new static($plugin);
	}

	/**
	 * Calendar constructor.
	 *
	 * @param EventManager $plugin
	 */
	public function __construct(EventManager $plugin)
	{
		$this->options = $plugin->getOptions();
	}

	/**
	 * @param Event $event
	 *
	 * @return $this
	 */
	public function addEvent(Event $event): self
	{
		$this->events[] = $event;

		return $this;
	}

	/**
	 * @param Event[] $events
	 *
	 * @return $this
	 */
	public function addEvents(array $events): self
	{
		foreach ($events as $event) {
			$this->addEvent($event);
		}

		return $this;
	}

	/**
	 * @param WP_Post $post
	 * @param string   $image
	 *
	 * @return $this
	 */
	public function addPost(WP_Post $post, string $image = 'thumbnail'): self
	{
		return $this->addEvent(new Event($post, $image, $this->options));
	}

	/**
	 *
	 */
	public function send(): void
	{
		if (empty($this->events)) {
			return;
		}

		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=' . $this->getFilename());
		header('Content-type: text/calendar; charset=utf-8');
		header('Pragma: 0');
		header('Expires: 0');

		exit($this->getCalendar());
	}

	/**
	 * @return string
	 */
	protected function getFilename(): string
	{
		if (count($this->events) === 1) {
			$event = reset($this->events);
			$name  = $event->name;
		} else if (count($this->events) > 1) {
			$event = end($this->events);
			$name  = get_bloginfo('name');
		} else {
			return '';
		}

		return sprintf('%s-%s.ics', urlencode($name), $event->startDate->format('Ymd-His'));
	}

	/**
	 * @return string
	 */
	protected function getCalendar(): string
	{
		$events = implode('', array_map([$this, 'getEvent'], $this->events));

		return $this->format($this->serialize([
			'BEGIN'        => 'VCALENDAR',
			'PRODID'       => '-//ic Event Manager/Calendar 2.0//EN',
			'VERSION'      => '2.0',
			'CALSCALE'     => 'GREGORIAN',
			'METHOD'       => 'PUBLISH',
			'X-WR-CALNAME' => $this->options->get('calendar.name') ? get_bloginfo('name') : null,
			'EVENTS'       => $events,
			'END'          => 'VCALENDAR',
		]));
	}

	/**
	 * @param Event $event
	 *
	 * @return string
	 */
	protected function getEvent(Event $event): string
	{
		return $this->serialize([
			'BEGIN'         => 'VEVENT',
			'UID'           => $event->uid,
			'DTSTART'       => $event->startDate->format('Ymd\THis\Z'),
			'DTEND'         => $event->endDate->format('Ymd\THis\Z'),
			'DTSTAMP'       => $event->dateStamp->format('Ymd\THis\Z'),
			'URL;VALUE=URI' => $event->uri,
			'URL'           => $event->web,
			'DESCRIPTION'   => $this->encode($event->description),
			'SUMMARY'       => $this->encode($event->summary),
			'LOCATION'      => empty($event->location) ? null : $this->encode(implode(' — ', $event->location)),
			'ORGANIZER'     => empty($event->organizer) ? null : $this->encode($event->organizer),
			'ATTACH'        => empty($event->image) ? null : $event->image->src,
			'TRANSP'        => 'OPAQUE',
			'END'           => 'VEVENT',
		]);
	}

	/**
	 * @param string $input
	 *
	 * @return string
	 */
	protected function encode(string $input): string
	{
		$input = preg_replace('/([\,;])/', '\\\$1', $input);
		$input = str_replace(["\n", "\r"], ["\\n", "\\r"], (string) $input);

		return $input;
	}

	/**
	 * @param array $input
	 *
	 * @return string
	 */
	protected function serialize(array $input): string
	{
		$serialized = Arr::map(array_filter($input), static function ($value, $key) {
			return ($key === 'EVENTS') ? $value : "$key:$value\r\n";
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
