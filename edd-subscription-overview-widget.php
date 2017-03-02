<?php

/*
Plugin Name: Easy Digital Downloads - Subscriptions Widget
Plugin URI: http://www.hudsonatwell.co
Description: Adds dashboard widget that shows deeper insight into subscriptions. 
Version: 2.4.5
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
                'callback' => array( __CLASS__ , 'display_subscription_overview_widget')
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

        $db = new EDD_Subscriptions_DB;
        $subscriptions = $db->get_subscriptions( array(
            'number'      => -1,
            'status'      => array('active','cancelled'),
            'period'      => 'month',
        ) );

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


        /* setup dates */
        $date = new DateTime();
        $today  = $date->format('m-d-Y');
        $date->modify('-1 day');
        $yesterday = $date->format('m-d-Y');

        foreach ($subscriptions as $key => $subscription) {

            if ( $today == date('m-d-Y', strtotime($subscription->created))) {
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


        ?>

        <!--[if lte IE 8]>
        <script language="javascript" type="text/javascript" src="/wp-content/plugins/lead-dashboard-widgets/assets/js/flot/excanvas.min.js"></script><![endif]-->
        <div class="edd_dashboard_widget">
            <div class="table table_totals">
                <table>
                    <thead>
                    <tr>
                        <td >
                            New Subscriptions
                        </td>
                        <td >
                            #
                        </td>
                        <td >
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
                            echo edd_currency_filter(edd_format_amount($data['active']['today']['total'] , true));
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
                            echo edd_currency_filter(edd_format_amount($data['active']['yesterday']['count'] , true));
                            ?>
                        </td>
                    </tr>
                </table>

                <table>
                    <thead>
                    <tr>
                        <td >
                            Canceled Subscriptions
                        </td>
                        <td >
                            #
                        </td>
                        <td >
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
                            echo edd_currency_filter(edd_format_amount($data['active']['today']['total'] , true));
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
                            echo edd_currency_filter(edd_format_amount($data['cancelled']['yesterday']['count'] , true));
                            ?>
                        </td>
                    </tr>
                </table>

                <table>
                    <thead>
                    <tr>
                        <td >
                            Total Subscription Value
                        </td>
                        <td >
                            #
                        </td>
                        <td >
                            Ammount
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="t">
                            Total Month Subscriptions
                        </td>
                        <td class="b">
                            <?php
                            echo $data['active']['month']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php echo edd_currency_filter(edd_format_amount($data['active']['month']['total'] , true)); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="t">
                            Total Annual Subscriptions
                        </td>
                        <td class="b">
                            <?php
                            echo $data['active']['year']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php echo edd_currency_filter(edd_format_amount($data['active']['year']['total'] , true)); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="t">
                            Combined Annual Total
                        </td>
                        <td class="b">
                            <?php
                            echo $data['active']['month']['count'] + $data['active']['year']['count'];
                            ?>
                        </td>
                        <td class="b">
                            <?php echo edd_currency_filter(edd_format_amount($data['active']['year']['total'] + ( $data['active']['month']['total'] * 12 ) , true)); ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }


    public static function add_inline_header_scripts() {

    }
}

new EDD_Subscriptions_Overview_Widget();
