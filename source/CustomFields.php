<?php

namespace ic\Plugin\EventManager;

use ic\Framework\Plugin\PluginClassDecorator;

/**
 * Class CustomFields
 *
 * @package ic\Plugin\EventManager
 */
class CustomFields
{

    public const ACF_NAME = 'acf_event';

    public const ACF_TYPE = 'acf';

    use PluginClassDecorator;

    /**
     * @param EventManager $plugin
     *
     * @return static
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public static function register(EventManager $plugin)
    {
        return new static($plugin);
    }

    /**
     * CustomFields constructor.
     *
     * @param EventManager $plugin
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct(EventManager $plugin)
    {
        $this->setPlugin($plugin);

        if (!$this->getOption('acf')) {
            global $wpdb;

            $id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_name = %s", self::ACF_TYPE, self::ACF_NAME));

            $this->setOption('acf', $id ? 'database' : 'class');
            $this->getOptions()->save();
        }

        if ($this->getOption('acf') === 'class') {
            $this->registerFields();
        }
    }

    /**
     * Register the custom fields if not present in the database.
     */
    protected function registerFields(): void
    {
        register_field_group(array(
            'id'         => self::ACF_NAME,
            'title'      => __('Event', $this->id()),
            'fields'     => array(
                array(
                    'key'           => 'field_52347fdcfd91b',
                    'label'         => __('Event website', $this->id()),
                    'name'          => 'url',
                    'type'          => 'text',
                    'default_value' => '',
                    'placeholder'   => '',
                    'prepend'       => '',
                    'append'        => '',
                    'formatting'    => 'none',
                    'maxlength'     => '',
                ),
                array(
                    'key'   => 'field_52340b8757ddb',
                    'label' => __('Date', $this->id()),
                    'name'  => '',
                    'type'  => 'tab',
                ),
                array(
                    'key'           => 'field_5234069bf9284',
                    'label'         => __('All day event', $this->id()),
                    'name'          => 'date_allday',
                    'type'          => 'true_false',
                    'message'       => '',
                    'default_value' => 0,
                ),
                array(
                    'key'           => 'field_5234069bf9285',
                    'label'         => __('Show end date', $this->id()),
                    'name'          => 'date_end_show',
                    'type'          => 'true_false',
                    'message'       => '',
                    'default_value' => 1,
                ),
                array(
                    'key'            => 'field_523405fcf9283',
                    'label'          => __('Start date', $this->id()),
                    'name'           => 'date_start',
                    'type'           => 'date_picker',
                    'required'       => 1,
                    'date_format'    => 'yymmdd',
                    'display_format' => 'dd/mm/yy',
                    'first_day'      => 1,
                ),
                array(
                    'key'               => 'field_5234113e674de',
                    'label'             => __('Start time', $this->id()),
                    'name'              => 'time_start_hour',
                    'type'              => 'select',
                    'conditional_logic' => array(
                        'status'   => 1,
                        'rules'    => array(
                            array(
                                'field'    => 'field_5234069bf9284',
                                'operator' => '!=',
                                'value'    => '1',
                            ),
                        ),
                        'allorany' => 'all',
                    ),
                    'choices'           => array(
                        '00' => '00',
                        '01' => '01',
                        '02' => '02',
                        '03' => '03',
                        '04' => '04',
                        '05' => '05',
                        '06' => '06',
                        '07' => '07',
                        '08' => '08',
                        '09' => '09',
                        10   => 10,
                        11   => 11,
                        12   => 12,
                        13   => 13,
                        14   => 14,
                        15   => 15,
                        16   => 16,
                        17   => 17,
                        18   => 18,
                        19   => 19,
                        20   => 20,
                        21   => 21,
                        22   => 22,
                        23   => 23,
                    ),
                    'default_value'     => '',
                    'allow_null'        => 0,
                    'multiple'          => 0,
                ),
                array(
                    'key'               => 'field_523415f84e7f7',
                    'label'             => __('Start time', $this->id()),
                    'name'              => 'time_start_minutes',
                    'type'              => 'select',
                    'conditional_logic' => array(
                        'status'   => 1,
                        'rules'    => array(
                            array(
                                'field'    => 'field_5234069bf9284',
                                'operator' => '!=',
                                'value'    => '1',
                            ),
                        ),
                        'allorany' => 'all',
                    ),
                    'choices'           => array(
                        '00' => '00',
                        '05' => '05',
                        10   => 10,
                        15   => 15,
                        20   => 20,
                        25   => 25,
                        30   => 30,
                        35   => 35,
                        40   => 40,
                        45   => 45,
                        50   => 50,
                        55   => 55,
                    ),
                    'default_value'     => '',
                    'allow_null'        => 0,
                    'multiple'          => 0,
                ),
                array(
                    'key'            => 'field_52340723f9285',
                    'label'          => __('End date', $this->id()),
                    'name'           => 'date_end',
                    'type'           => 'date_picker',
                    'date_format'    => 'yymmdd',
                    'display_format' => 'dd/mm/yy',
                    'first_day'      => 1,
                ),
                array(
                    'key'               => 'field_52341656b1410',
                    'label'             => __('End time', $this->id()),
                    'name'              => 'time_end_hour',
                    'type'              => 'select',
                    'conditional_logic' => array(
                        'status'   => 1,
                        'rules'    => array(
                            array(
                                'field'    => 'field_5234069bf9284',
                                'operator' => '!=',
                                'value'    => '1',
                            ),
                        ),
                        'allorany' => 'all',
                    ),
                    'choices'           => array(
                        '00' => '00',
                        '01' => '01',
                        '02' => '02',
                        '03' => '03',
                        '04' => '04',
                        '05' => '05',
                        '06' => '06',
                        '07' => '07',
                        '08' => '08',
                        '09' => '09',
                        10   => 10,
                        11   => 11,
                        12   => 12,
                        13   => 13,
                        14   => 14,
                        15   => 15,
                        16   => 16,
                        17   => 17,
                        18   => 18,
                        19   => 19,
                        20   => 20,
                        21   => 21,
                        22   => 22,
                        23   => 23,
                    ),
                    'default_value'     => '',
                    'allow_null'        => 0,
                    'multiple'          => 0,
                ),
                array(
                    'key'               => 'field_52341753b1411',
                    'label'             => __('End time', $this->id()),
                    'name'              => 'time_end_minutes',
                    'type'              => 'select',
                    'conditional_logic' => array(
                        'status'   => 1,
                        'rules'    => array(
                            array(
                                'field'    => 'field_5234069bf9284',
                                'operator' => '!=',
                                'value'    => '1',
                            ),
                        ),
                        'allorany' => 'all',
                    ),
                    'choices'           => array(
                        '00' => '00',
                        '05' => '05',
                        10   => 10,
                        15   => 15,
                        20   => 20,
                        25   => 25,
                        30   => 30,
                        35   => 35,
                        40   => 40,
                        45   => 45,
                        50   => 50,
                        55   => 55,
                    ),
                    'default_value'     => '',
                    'allow_null'        => 0,
                    'multiple'          => 0,
                ),
                array(
                    'key'   => 'field_52348033fd91d',
                    'label' => __('Location', $this->id()),
                    'name'  => '',
                    'type'  => 'tab',
                ),
                array(
                    'key'           => 'field_523480814e0b5',
                    'label'         => __('Venue', $this->id()),
                    'name'          => 'venue',
                    'type'          => 'text',
                    'default_value' => '',
                    'placeholder'   => '',
                    'prepend'       => '',
                    'append'        => '',
                    'formatting'    => 'html',
                    'maxlength'     => '',
                ),
                array(
                    'key'           => 'field_523480ff4e0b6',
                    'label'         => __('Address', $this->id()),
                    'name'          => 'address',
                    'type'          => 'text',
                    'default_value' => '',
                    'placeholder'   => '',
                    'prepend'       => '',
                    'append'        => '',
                    'formatting'    => 'html',
                    'maxlength'     => '',
                ),
                array(
                    'key'           => 'field_523481324e0b7',
                    'label'         => __('City', $this->id()),
                    'name'          => 'city',
                    'type'          => 'text',
                    'default_value' => '',
                    'placeholder'   => '',
                    'prepend'       => '',
                    'append'        => '',
                    'formatting'    => 'none',
                    'maxlength'     => '',
                ),
            ),
            'location'   => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => EventManager::POST_TYPE,
                        'order_no' => 0,
                        'group_no' => 0,
                    ),
                ),
            ),
            'options'    => array(
                'position'       => 'normal',
                'layout'         => 'default',
                'hide_on_screen' => array(
                    0 => 'custom_fields',
                    1 => 'revisions',
                    2 => 'format',
                    3 => 'categories',
                    4 => 'tags',
                ),
            ),
            'menu_order' => 10,
        ));
    }

}
