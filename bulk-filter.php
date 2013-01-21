<?php
/**
 * Bulk Modify Context in Array Using Anonymous Function
 * Require PHP 5.3
 * 
 * 
 * @since 0.1.0
 */

/**
 * Helper function to modify each contexts
 * 
 * @param $id		integer		widget id
 * @param $instance	string		target contexts
 * @since 0.1.0
 */
function atomic_widget_add_filter( $id, $instance ){

	/* anonymous function */
	$anon = function () use( &$instance ){
		return $instance;
	};

	/* add filter */
	add_filter( 'atomic_widget_' . $id, $anon );
}


/* add it in init hook */
add_action( 'init','atomic_widget_bulk_filter' );


/**
 * Bulk Filter
 * 
 * @since 0.1.0
 */
function atomic_widget_bulk_filter(){

	/* bulk filter atomic widget instances in one array: require php 5.3 */
	$instances = apply_filters( 'atomic_widgets', array() );

	/* if it's not empty */
	if ( !empty( $instances ) ){

		/* for each widget id */
		foreach ( $instances as $id => $instance ){

			/* filter it */
			atomic_widget_add_filter( $id, $instance );
		}
	}
}