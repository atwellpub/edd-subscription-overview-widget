<?php

/*
Plugin Name: Easy Digital Downloads - Subscriptions Widget
Plugin URI: http://www.hudsonatwell.co
Description: Adds dashboard widget that shows deeper insight into subscriptions. 
Version: 0.9.0
Author: Hudson Atwell
Author URI: http://www.hudsonatwell.co

*/

class EDD_Subscriptions_Overview_Widget {

    public function __construct() {

        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));

        add_action('admin_enqueue_scripts', array(__CLASS__, 'register_admin_scripts'));

        add_action('admin_head', array(__CLASS__, 'add_inline_header_scripts'));
    }


    public static function add_dashboard_widgets() {

        if (!current_user_can('activate_plugins')) {
            return;
        }

        $custom_dashboard_widgets = array(
            'edd-business-overview' => array(
                'title' => __('Subscriptions Overview', 'inbound-pro'),
                'callback' => array(__CLASS__, 'display_subscription_overview_widget')
            )
        );

        foreach ($custom_dashboard_widgets as $widget_id => $options) {
            wp_add_dashboard_widget(
                $widget_id,
                $options['title'],
                $options['callback']
            );
        }
    }

    public static function register_admin_scripts($hook) {


    }


    public static function display_subscription_overview_widget() {
        global $wpdb;

        /* get settings array from database
        $settings = get_option('edd_subscriptions_widget' , array());*/
        /* determine default view
        $default_view = (isset($settings['default_view'])) ? $settings['default_views'] : 'all'; */

        /* setup dates */
        $date = new DateTime();
        $current_year = $date->format('Y');
        $today = $date->format('m-d-Y');
        $date->modify('-1 day');
        $yesterday = $date->format('m-d-Y');
        $date->modify('-1 year');
        $past_year = $date->format('Y');

        /* get default view */
        $default_view = (isset($_GET['subscriptions_view'])) ? $_GET['subscriptions_view'] : $current_year;

        /* ready EDD DB connector */
        $db = new EDD_Subscriptions_DB;

        /* Get Subscriptions */
        $subscriptions = $db->get_subscriptions(array(
            'number' => -1,
            'status' => array('active', 'cancelled'),
            'period' => 'month',
        ));

        $data = array(
            'active' => array(
                'today' => array(
                    'count' => 0,
                    'total' => 0
                ),
                'yesterday' => array(
                    'count' => 0,
                    'total' => 0
                ),
                'month' => array(
                    'count' => 0,
                    'total' => 0,
                    'subscriptions' => array()
                ),
                'year' => array(
                    'count' => 0,
                    'total' => 0,
                    'subscriptions' => array()
                )
            ),
            'cancelled' => array(
                'today' => array(
                    'count' => 0,
                    'total' => 0
                ),
                'yesterday' => array(
                    'count' => 0,
                    'total' => 0
                ),
                'month' => array(
                    'count' => 0,
                    'total' => 0,
                    'subscriptions' => array()
                ),
                'year' => array(
                    'count' => 0,
                    'total' => 0,
                    'subscriptions' => array()
                )
            ),

        );


        foreach ($subscriptions as $key => $subscription) {

            /* Filter data by date  */
            if ($default_view != date('Y', strtotime($subscription->created)) && $default_view != 'all') {
                continue;
            }

            if ($today == date('m-d-Y', strtotime($subscription->created))) {
                $data[$subscription->status]['today']['count']++;
                $data[$subscription->status]['today']['total'] = $data[$subscription->status]['today']['total'] + $subscription->recurring_amount;
            }

            if ($yesterday == date('m-d-Y', strtotime($subscription->created))) {
                $data[$subscription->status]['yesterday']['count']++;
                $data[$subscription->status]['yesterday']['total'] = $data[$subscription->status]['yesterday']['total'] + $subscription->recurring_amount;
            }

            switch ($subscription->period) {
                case 'month':
                    $data[$subscription->status]['month']['count']++;
                    $data[$subscription->status]['month']['total'] = $data[$subscription->status]['month']['total'] + $subscription->recurring_amount;
                    $data[$subscription->status]['month']['total']['subscriptions'][] = $subscription;
                    break;
                case 'year':
                    $data[$subscription->status]['year']['count']++;
                    $data[$subscription->status]['year']['total'] = $data[$subscription->status]['year']['total'] + $subscription->recurring_amount;
                    $data[$subscription->status]['year']['total']['subscriptions'][] = $subscription;
                    break;
            }
        }

        /* calculate active to canceled ration */
        $month_lcd = self::get_least_common_demoninator($data['active']['month']['total'], $data['cancelled']['month']['total']);
        $year_lcd = self::get_least_common_demoninator($data['active']['year']['total'], $data['cancelled']['year']['total']);
        $combined_active = $data['active']['month']['total'] + $data['active']['year']['total'];
        $combined_cancelled = $data['cancelled']['month']['total'] + $data['cancelled']['year']['total'];
        $combined_lcd = self::get_least_common_demoninator($combined_month, $combined_cancelled);


        ?>

        <!--[if lte IE 8]>
        <script language="javascript" type="text/javascript" src="/wp-content/plugins/lead-dashboard-widgets/assets/js/flot/excanvas.min.js"></script><![endif]-->
        <div class="edd_dashboard_widget">
            <div class="table table_totals">
                <ul>
                    <li><?php


                        if ($default_view != $current_year ) {
                            echo '<a href="?subscriptions_view='.$current_year.'">Current Year</a> | ';
                        } else  {
                            echo 'Current Year | ';
                        }

                        if ($default_view != $past_year) {
                            echo '<a href="?subscriptions_view='.$past_year.'">Past Year</a> | ';
                        } else  {
                            echo 'Past Year | ';
                        }

                        if ($default_view != 'all') {
                            echo '<a href="?subscriptions_view=all">Lifetime</a> ';
                        } else  {
                            echo 'Lifetime ';
                        }

                        ?>
                    </li>
                    <li>
                </ul>
                <table>
                    <thead>
                    <tr>
                        <td>
                            New Active Subscriptions
                        </td>
                        <td>
                            #
                        </td>
                        <td>
                            Ammount
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="t">
                            Today
                        </td>
                        <td class="b">
                            <?php
                            echo $data['active']['today']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php
                            echo edd_currency_filter(edd_format_amount($data['active']['today']['total'], true));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="t">
                            Yesterday
                        </td>
                        <td class="b">
                            <?php
                            echo $data['active']['yesterday']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php
                            echo edd_currency_filter(edd_format_amount($data['active']['yesterday']['count'], true));
                            ?>
                        </td>
                    </tr>
                </table>

                <table>
                    <thead>
                    <tr>
                        <td>
                            New Cancelled Subscriptions
                        </td>
                        <td>
                            #
                        </td>
                        <td>
                            Ammount
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="t">
                            Today
                        </td>
                        <td class="b">
                            <?php
                            echo $data['cancelled']['today']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php
                            echo edd_currency_filter(edd_format_amount($data['cancelled']['today']['total'], true));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="t">
                            Yesterday
                        </td>
                        <td class="b">
                            <?php
                            echo $data['cancelled']['yesterday']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php
                            echo edd_currency_filter(edd_format_amount($data['cancelled']['yesterday']['count'], true));
                            ?>
                        </td>
                    </tr>
                </table>

                <table>
                    <thead>
                    <tr>
                        <td>
                            Total Active
                        </td>
                        <td>
                            #
                        </td>
                        <td>
                            Ammount
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="t">
                            Total Month
                        </td>
                        <td class="b">
                            <?php
                            echo $data['active']['month']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php echo edd_currency_filter(edd_format_amount($data['active']['month']['total'], true)); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="t">
                            Total Annual
                        </td>
                        <td class="b">
                            <?php
                            echo $data['active']['year']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php echo edd_currency_filter(edd_format_amount($data['active']['year']['total'], true)); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="t">
                            Combined Annual
                        </td>
                        <td class="b">
                            <?php
                            echo $data['active']['month']['count'] + $data['active']['year']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php echo edd_currency_filter(edd_format_amount($data['active']['year']['total'] + ($data['active']['month']['total'] * 12), true)); ?>
                        </td>
                    </tr>
                    </tbody>
                </table>


                <table>
                    <thead>
                    <tr>
                        <td>
                            Total Cancelled
                        </td>
                        <td>
                            #
                        </td>
                        <td>
                            Ammount
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="t">
                            Total Month
                        </td>
                        <td class="b">
                            <?php
                            echo $data['cancelled']['month']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php echo edd_currency_filter(edd_format_amount($data['cancelled']['month']['total'], true)); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="t">
                            Total Annual
                        </td>
                        <td class="b">
                            <?php
                            echo $data['cancelled']['year']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php echo edd_currency_filter(edd_format_amount($data['cancelled']['year']['total'], true)); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="t">
                            Combined Annual
                        </td>
                        <td class="b">
                            <?php
                            echo $data['cancelled']['month']['count'] + $data['cancelled']['year']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php echo edd_currency_filter(edd_format_amount($data['cancelled']['year']['total'] + ($data['cancelled']['month']['total'] * 12), true)); ?>
                        </td>
                    </tr>
                    </tbody>
                </table>


                <table>
                    <thead>
                    <tr>
                        <td>
                            Performance Index
                        </td>
                        <td>
                            Index
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="t">
                            Month Subscriptions
                        </td>
                        <td class="b">
                            <?php
                            $t = (($data['active']['month']['total'] / $month_lcd) / ($data['cancelled']['month']['total'] / $month_lcd));
                            echo number_format($t, '2', '.', '');
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="t">
                            Year Subscriptions
                        </td>
                        <td class="b">
                            <?php
                            $t = (($data['active']['year']['total'] / $year_lcd) / ($data['cancelled']['year']['total'] / $year_lcd));
                            echo number_format($t, '2', '.', '');
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="t">
                            Combined Subscriptions
                        </td>
                        <td class="b">
                            <?php

                            $t = (($combined_active / $combined_lcd) / ($combined_cancelled / $combined_lcd));
                            echo number_format($t, '2', '.', '');
                            ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }


    public static function get_least_common_demoninator($a, $b) {

        while ($b != 0) {
            $remainder = $a % $b;
            $a = $b;
            $b = $remainder;
        }
        return abs($a);

    }


    public static function add_inline_header_scripts() {

    }
}

new EDD_Subscriptions_Overview_Widget();
