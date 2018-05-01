<?php

namespace ic\Plugin\EventManager;

use ic\Framework\Support\Str;
use ic\Framework\Support\TextLimiter;

/**
 * Class Event
 *
 * @package ic\Plugin\EventManager
 */
class Event
{

	/**
	 * @var Events
	 */
	private $calendar;

	/**
	 * @var int
	 */
	private $uid;

	/**
	 * @var string
	 */
	private $uri;

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
	 * @var string
	 */
	private $location;

	/**
	 * @var string
	 */
	private $organizer;

	/**
	 * CalendarEvent constructor.
	 *
	 * @param Events $calendar
	 * @param array  $event  {
	 *
	 * @type int     $uid
	 * @type string  $uri
	 * @type string  $description
	 * @type string  $summary
	 * @type string  $organizer
	 * @type string  $image
	 *                      }
	 *
	 * @param array  $fields {
	 *
	 * @type string  $date_start
	 * @type string  $date_end
	 * @type string  $date_allday
	 * @type string  $venue
	 * @type string  $address
	 * @type string  $city
	 *                       }
	 */
	public function __construct(Events $calendar, array $event, array $fields)
	{
		$this->calendar    = $calendar;
		$this->uid         = $event['uid'];
		$this->dateStamp   = $this->date();
		$this->startDate   = $this->startDate($fields);
		$this->endDate     = $this->endDate($fields);
		$this->uri         = $event['uri'];
		$this->description = Str::whitespace($event['description']);
		$this->summary     = $this->summary($event['summary']);
		$this->location    = $this->location($fields);
		$this->organizer   = $event['organizer'];
		$this->image       = $event['image'];
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
	 * @return string
	 */
	public function __toString()
	{
		$event = $this->event();

		return $this->calendar->serialize($event);
	}

	/**
	 * @return array
	 */
	public function event(): array
	{
		return array_filter([
			'BEGIN'         => 'VEVENT',
			'UID'           => $this->uid,
			'DTSTART'       => $this->startDate->format('Ymd\THis\Z'),
			'DTEND'         => $this->endDate->format('Ymd\THis\Z'),
			'DTSTAMP'       => $this->dateStamp->format('Ymd\THis\Z'),
			'URL;VALUE=URI' => $this->uri,
			'DESCRIPTION'   => $this->encode($this->description),
			'SUMMARY'       => $this->encode($this->summary),
			'LOCATION'      => $this->encode($this->location),
			'ORGANIZER'     => $this->encode($this->organizer),
			'ATTACH'        => $this->image,
			'TRANSP'        => 'OPAQUE',
			'END'           => 'VEVENT',
		]);
	}

	/**
	 * @param string $input
	 *
	 * @return \DateTime
	 */
	private function date(string $input = 'now'): \DateTime
	{
		$date = new \DateTime($input);
		$date->setTimezone(new \DateTimeZone('UTC'));

		return $date;
	}

	/**
	 * @param array $event
	 *
	 * @return \DateTime
	 */
	private function startDate(array $event): \DateTime
	{
		$date = mysql2date('Y-m-d', $event['date_start']);
		$time = $event['date_allday'] ? '00:00:00' : sprintf('%d:%d:00', $event['time_start_hour'], $event['time_start_minutes']);

		return $this->date("$date $time");
	}

	/**
	 * @param array $event
	 *
	 * @return \DateTime
	 */
	private function endDate(array $event): \DateTime
	{
		if (empty($event['date_end']) || ($event['date_end'] === $event['date_start'])) {
			$date = mysql2date('Y-m-d', $event['date_start']);
			$time = $event['date_allday'] ? '23:59:59' : sprintf('%d:%d:00', $event['time_end_hour'], $event['time_end_minutes']);
		} else {
			$date = mysql2date('Y-m-d', $event['date_end']);
			$time = $event['date_allday'] ? '00:00:00' : sprintf('%d:%d:00', $event['time_end_hour'], $event['time_end_minutes']);
		}

		return $this->date("$date $time");
	}

	/**
	 * @param string $summary
	 *
	 * @return string
	 */
	private function summary(string $summary): string
	{
		$summary = Str::whitespace($summary);
		$summary = trim($summary);

		if (empty($summary)) {
			return '';
		}

		$length  = Str::length($summary);
		$summary = TextLimiter::words($summary, 60);

		if (!empty($summary) && ($length > Str::length($summary))) {
			$summary .= '…';
		}

		return $summary;
	}

	/**
	 * @param array $event
	 *
	 * @return string
	 */
	private function location(array $event): string
	{
		$keys     = array_flip(['venue', 'address', 'city']);
		$location = array_intersect_key(array_replace($keys, $event), $keys);
		$location = array_filter($location);

		if (empty($location)) {
			$location = '';
		} else {
			$location = implode(' — ', $location);
		}

		return $location;
	}

	/**
	 * @param string $input
	 *
	 * @return string
	 */
	private function encode(string $input): string
	{
		$input = preg_replace('/([\,;])/', '\\\$1', $input);
		$input = str_replace(["\n", "\r"], ["\\n", "\\r"], $input);

		return $input;
	}

}