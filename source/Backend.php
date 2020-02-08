<?php

namespace ic\Plugin\EventManager;

use ic\Framework\Plugin\PluginClass;
use ic\Framework\Settings\Form\Section;
use ic\Framework\Settings\Settings;
use ic\Framework\Support\Arr;
use InvalidArgumentException;

/**
 * Class Backend
 *
 * @package ic\Plugin\EventManager
 *
 * @method EventManager getPlugin()
 */
class Backend extends PluginClass
{

	/**
	 * @inheritdoc
	 *
	 * @throws InvalidArgumentException
	 */
	protected function configure(): void
	{
		parent::configure();

		$this->hook()
			 ->on('acf/load_field/name=time_start_hour', 'setFieldHours')
			 ->on('acf/load_field/name=time_end_hour', 'setFieldHours')
			 ->on('acf/load_field/name=time_start_minutes', 'setFieldMinutes')
			 ->on('acf/load_field/name=time_end_minutes', 'setFieldMinutes')
			 ->after('acf/save_post', 'filterEventDate')
			 ->on('wp_insert_post_data', 'filterEventStatus')
			 ->off('future_' . EventManager::POST_TYPE, '_future_post_hook');

		$this->addBackScript('event-manager.js', [
			'depends' => ['jquery'],
			'hooks'   => ['post.php', 'post-new.php'],
			'version' => $this->version(),
		]);

		$this->addBackStyle('event-manager.css', [
			'hooks'   => ['post.php', 'post-new.php'],
			'version' => $this->version(),
		]);
	}

	/**
	 * @inheritdoc
	 */
	protected function initialize(): void
	{
		Settings::siteOptions($this->getOptions(), $this->name())
				->section('organizer', function (Section $section) {
					$section->title(__('Organizer', $this->id()))
							->checkbox('organizer.enable', __('Enable', $this->id()), [
								'label' => __('Allows to specify an organizer for the event.', $this->id()),
							])
							->text('organizer.default', __('Default organizer', $this->id()), [
								'class' => 'regular-text',
							]);
				})
				->section('calendar', function (Section $section) {
					$section->title(__('Calendar', $this->id()))
							->checkbox('calendar.enable', __('Enable', $this->id()), [
								'label' => __('Creates a feed with the events in iCalendar format.', $this->id()),
							])
							->checkbox('calendar.name', __('Include the blog name', $this->id()), [
								'label'       => __('Specifies the blog name as the calendar name.', $this->id()),
								'description' => __('Uses the property <code>X-WR-CALNAME</code>. May cause problems in some applications.', $this->id()),
							])
							->number('calendar.limit', __('Limit the events', $this->id()), [
								'min'         => 0,
								'description' => __('Type <code>0</code> to include all events.', $this->id()),
							])
							->number('calendar.words', __('Number of words in the summary', $this->id()), [
								'min' => 0
							])
							->image_sizes('calendar.image', __('Image size', $this->id()));
				})
				->finalization(function (array $values) {
					if (Arr::get($values, 'calendar.enable')) {
						add_feed(EventManager::FEED_TYPE, [
							$this->getPlugin(),
							'getCalendar',
						]);
					}

					flush_rewrite_rules();
				});

		$organizer = $this->getOption('organizer.enable');

		Settings::optionsPermalink($this->getOptions())
				->section('events', function (Section $section) use ($organizer) {
					$section->title(__('Custom structures for events', $this->id()))
							->text('slug.event', __('Event base', $this->id()), [
								'class' => 'regular-text code',
							]);

					if ($organizer) {
						$section->text('slug.organizer', __('Organizer base', $this->id()), [
							'class' => 'regular-text code',
						]);
					}
				});
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	protected function setFieldHours(array $field): array
	{
		$hours = $this->getTimeRange(23);

		$field['choices'] = array_combine($hours, $hours);

		return $field;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	protected function setFieldMinutes(array $field): array
	{
		$minutes = $this->getTimeRange(55, 5);

		$field['choices'] = array_combine($minutes, $minutes);

		return $field;
	}

	/**
	 * @param int $id
	 *
	 * @return int
	 */
	protected function filterEventDate($id): int
	{
		$post = get_post($id);

		if ($date = $this->getPlugin()->getEventDate($post)) {
			$this->hook()->disable('filterPostDate');

			$post_date = sprintf('%s-%s-%s 00:00:00', $date['year'], $date['month'], $date['day']);

			if ($post_date !== $post->post_date) {
				$post->post_date     = $post_date;
				$post->post_date_gmt = '';

				wp_update_post($post);
			}
		}

		return (int) $id;
	}

	/**
	 * @param array $post
	 *
	 * @return array
	 */
	protected function filterEventStatus(array $post): array
	{
		if (($post['post_type'] === EventManager::POST_TYPE) && ($post['post_status'] === 'future')) {
			$post['post_status'] = 'publish';
		}

		return $post;
	}

	/**
	 * @param int $limit
	 * @param int $step
	 *
	 * @return array
	 */
	protected function getTimeRange(int $limit, int $step = 1): array
	{
		return array_map(static function ($number) {
			return str_pad((string) $number, 2, '0', STR_PAD_LEFT);
		}, range(0, $limit, $step));
	}

}
