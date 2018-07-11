<?php
/**
 * Runs on Uninstall of Software License Manager
 *
 * @package   Software License Manager
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */

add_filter( 'cron_schedules', 'subscriptio_check_status' );

function subscriptio_check_status( $schedules ) {
    $schedules['subcriptio_interval_check'] = array(
            'interval'  => 180,
            'display'   => __( 'Every 3 Minutes', 'pilotkit' )
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'subscriptio_check_status' ) ) {
    wp_schedule_event( time(), 'subcriptio_interval_check', 'subscriptio_check_status' );
}

// Hook into that action that'll fire every three minutes
add_action( 'subscriptio_check_status', 'subcriptio_interval_check_event_func' );


function subcriptio_interval_check_event_func() {

    global $wpdb;
    $result = '';
}