<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_option( 'amer_settings' );
delete_option( 'amer_last_good' );
delete_option( 'amer_cache_salt' );

global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_amer\\_doc\\_%' OR option_name LIKE '\\_transient\\_timeout\\_amer\\_doc\\_%'" ); // phpcs:ignore
