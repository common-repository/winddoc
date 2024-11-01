<?php

/*
Plugin Name: WindDoc
Version: 1.9
Description: Integrazione con Software WindDoc
Author: Luca Morri
Author URI: http://www.winddoc.com
*/

global $winddoc_db_version;
$winddoc_db_version = '1.1';

define('WC_WINDDOC_PLUGIN_PATH', plugin_dir_path(__FILE__));

function winddoc_install() {

    global $wpdb;
    global $winddoc_db_version;

    $table_name = $wpdb->prefix . 'winddoc_sinc_order';
    $charset_collate = $wpdb->get_charset_collate();
    $sql= "CREATE TABLE IF NOT EXISTS `".$table_name."`(

      `id` int(11) unsigned NOT NULL auto_increment,
      `data_creazione` DateTime,
      `data_modifica` DateTime,
      `sales_flat_order_id` int(11),
      `id_ordine_winddoc` CHAR(36),
      `id_invoice_winddoc` CHAR(36),
      `customer_id` CHAR(36),
      `url_invoice_winddoc` text,
      `url_ordine_winddoc` text,
      PRIMARY KEY (`id`)
    ) ".$charset_collate.";";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    
    update_option( 'winddoc_db_version', $winddoc_db_version );
    // continua
}
register_activation_hook( __FILE__, 'winddoc_install' );

function winddoc_update_db_check() {

    global $winddoc_db_version;
    
    if ( get_site_option( 'winddoc_db_version' ) != $winddoc_db_version ) {

        winddoc_install();
       

    }
}
add_action( 'plugins_loaded', 'winddoc_update_db_check' );

require_once 'src/WindDoc_Settings.php';
require_once 'src/WindDoc_Helper.php';
require_once 'src/WindDoc_Action.php';
$winddoc_settings = new WindDoc_Settings();

if (is_admin()) {

}


function winddoc_enqueue_datepicker() {
    // Load the datepicker script (pre-registered in WordPress).
    wp_enqueue_script( 'jquery-ui-datepicker' );

    // You need styling for the datepicker. For simplicity I've linked to the jQuery UI CSS on a CDN.
    
    wp_enqueue_style( 'jquery-ui' );  
}
add_action( 'wp_enqueue_scripts', 'winddoc_enqueue_datepicker' );

