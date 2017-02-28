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
			'status'      => 'active',
			'period'      => 'month',
		) );
        
        $month_subscriptions_total = 0;
        $year_subscriptions_total = 0;
        $month_subscriptions = array();
        $year_subscriptions = array();
        
        foreach ($subscriptions as $key => $subscription) {
            switch ($subscription->period) {
                case 'month':                    
                    $month_subscriptions[] = $subscription;
                    $month_subscriptions_total = $month_subscriptions_total + (int) $subscription->recurring_amount;
                    break;
                case 'year':
                    $year_subscriptions[] = $subscription;                    
                    $year_subscriptions_total = $year_subscriptions_total + (int) $subscription->recurring_amount;
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
                            <td colspan="2">
                                Subscriptions						
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="t sales">
                                Monthly
                            </td>
                            <td class="last b b-earnings">
                                <?php echo edd_currency_filter(edd_format_amount($month_subscriptions_total , true)); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="sales">
                                Yearly						
                            </td>
                            <td class="last ">
                                <?php echo edd_currency_filter(edd_format_amount($year_subscriptions_total , true)); ?>				
                            </td>
                        </tr>
                        <tr>
                            <td class="sales">
                                Total Annual
                            </td>
                            <td class="sales">
                                <?php echo edd_currency_filter(edd_format_amount($year_subscriptions_total + ( $month_subscriptions_total * 12 ) , true)); ?>
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
