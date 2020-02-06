<?php
/*
Plugin Name: Paid Memberships Pro LevelMeta (Addon)
Plugin URI: #
Description: #
Author: PrakharKant
Version: 1.0
Author URI: #
*/ 

error_reporting(E_ALL); 
register_activation_hook( __FILE__, 'pkLm_plugin_activate' );
function pkLm_plugin_activate(){

    // Require parent plugin
    if ( ! is_plugin_active( 'paid-memberships-pro/paid-memberships-pro.php' ) and current_user_can( 'activate_plugins' ) ) { 
    	// Deactivate the plugin
    	deactivate_plugins(__FILE__);
        
        // Throw an error in the wordpress admin console
		$error_message = __('This plugin requires <a href="https://www.paidmembershipspro.com/">Paid Memberships Pro</a> plugin to be active!'); 

		die($error_message);

    }
} 


function pk_update_pmprolevel_meta($object_id, $meta_key, $meta_value) { 
	global $wpdb; 

	$table 		= $wpdb->pmpro_membership_levelmeta; 
	$column 	= 'pmpro_membership_level_id';

	$object_id 	= absint( $object_id );

	if ( ! $object_id ) { return false; }

	// expected_slashed ($meta_key)
	$meta_key 	= wp_unslash($meta_key);
	$meta_value = wp_unslash($meta_value);
	$meta_value = sanitize_meta( $meta_key, $meta_value, $column );
	$meta_value = maybe_serialize( $meta_value );

	$meta_ids 	= $wpdb->get_col( 
					$wpdb->prepare( 
						"SELECT meta_id FROM $table WHERE meta_key = %s AND $column = %d", 
						$meta_key, 
						$object_id 
					) 
				);

	if ( empty( $meta_ids ) ) { 
		$result = $wpdb->insert( $table, array(
			$column => $object_id,
			'meta_key' => $meta_key,
			'meta_value' => $meta_value
		) );

		$meta_id = (int) $wpdb->insert_id; 
		return $meta_id; 
	}

	$data  = compact( 'meta_value' );
	$where = array( $column => $object_id, 'meta_key' => $meta_key );

	$result = $wpdb->update( $table, $data, $where );

	var_dump($result); 
}


function pk_delete_pmprolevel_meta($object_id, $meta_key, $meta_value = '') { 
	global $wpdb; 

	$table 		= $wpdb->pmpro_membership_levelmeta; 
	$column 	= 'pmpro_membership_level_id';

	if ( ! $meta_key || ! is_numeric( $object_id ) ) { 
		return false; 
	}

	$object_id 	= absint( $object_id );

	if ( ! $object_id ) { return false; }

	// expected_slashed ($meta_key)
	$meta_key 	= wp_unslash($meta_key);
	$meta_value = wp_unslash($meta_value);
	$meta_value = maybe_serialize( $meta_value );

	$query 		= $wpdb->prepare(
		"DELETE FROM $table WHERE $column = %d AND meta_key = %s ", 
		$object_id, 
		$meta_key
	); 

	if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value )
		$query .= $wpdb->prepare(" AND meta_value = %s", $meta_value );

	$count 		= $wpdb->query($query);

	if ( !$count ) 
		return false; 

	return true; 
} 


function pk_get_pmprolevel_meta($object_id, $meta_key = '', $single = false) { 
	global $wpdb; 

	$table 		= $wpdb->pmpro_membership_levelmeta; 
	$column 	= 'pmpro_membership_level_id'; 

	if ( ! is_numeric( $object_id ) ) { 
		return false; 
	} 

	$object_id 	= absint( $object_id ); 

	if ( ! $object_id ) { return false; } 

	$query 		= "SELECT * FROM $table WHERE $column = $object_id"; 

	if( !$meta_key ) { 
		$result = $wpdb->get_results($query); 
	} 

	$query 		.= " AND meta_key = '$meta_key'"; 
	$result 	= $wpdb->get_row($query); 

	if( !$result ) 
		return false; 

	if( !$single ) 
		return $result; 
 
	$value 		= maybe_unserialize($result->meta_value); 
	return $value; 
} 

/*
* delete all meta data on deletion of membership deletion
*/

add_action('pmpro_delete_membership_level', function($level_id){ 
	global $wpdb; 

	$table 		= $wpdb->pmpro_membership_levelmeta;
	$column 	= 'pmpro_membership_level_id'; 

	$meta_ids 	= $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $table WHERE $column = %d ", $level_id ));
	
	foreach ( $meta_ids as $mid ) {
		$result = (bool) $wpdb->delete( $table, array( 'meta_id' => $mid ) );
	} 

}); 


