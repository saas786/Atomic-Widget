<?php
/**
 * Plugin Name: Atomic Widget
 * Plugin URI: http://shellcreeper.com/
 * Description: Display widget based on Hybrid Core atomic context. 
 * Version: 0.1.0-beta
 * Author: David Chandra Purnama
 * Author URI: http://shellcreeper.com/
 *
 * This plugin is similar to widget logic plugin by Alan Trewartha but alot simpler.
 * And it uses Hybrid Core - Atomic Context by Justin Tadlock.
 * 
 * This plugins is based on:
 * 1. Conditional Widgets by Jason Lemahieu and Kevin Graeme.
 *		http://wordpress.org/extend/plugins/conditional-widgets/ 
 * 2. Widget Logic by Alan Trewartha.
 *		http://wordpress.org/extend/plugins/widget-logic/
 * 3. Widget Context by Kaspars Dambis.
 *		http://wordpress.org/extend/plugins/widget-context/
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms  
 * of the GNU General Public License version 2, as published by the Free Software Foundation.
 * You may NOT assume that you can use any other version of the GPL.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @version 0.1.0
 * @author David Chandra Purnama <david.warna@gmail.com>
 * @copyright Copyright (c) 2013, David Chandra Purnama
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/* Hooks to 'plugins_loaded'  */
add_action( 'plugins_loaded','atomic_widget_setup' );

function atomic_widget_setup(){

	/* create form in each widgets */
	add_action( 'in_widget_form', 'atomic_widgets_form', 10, 3 );

	/* front end output */
	add_filter( 'widget_display_callback', 'atomic_widgets_display' );

	/* update widget instance */
	add_filter( 'widget_update_callback', 'atomic_widgets_update', 10, 2 );

}

/**
 * Display the form at the bottom of each widget.
 *
 * @since 0.1.0
 */
function atomic_widgets_form( $widget, $return, $instance ){

	/* $return */
	if ( $return == 'noform') {
		$return = true;
	}

	/* get instance */
	$instance = atomic_widgets_init_instance( $instance );

	/* HTML Form */
	?>
	<p><label for="atomic_context-<?php echo $widget->id; ?>">Atomic Context:</label><textarea class="widefat" rows="2" id="atomic_context-<?php echo $widget->id; ?>" name="atomic_context"><?php echo $instance['atomic_context']; ?></textarea></p>

	<?php
}


/**
 * Save Instance.
 *
 * @since 0.1.0
 */
function atomic_widgets_update( $new_instance, $old_instance ) {

	$instance = $new_instance;

	/* atomic context instance */
	$instance['atomic_context'] = $_POST['atomic_context'];

	return $instance;
}

/**
 * Front end display output.
 *
 * @since 0.1.0
 */
function atomic_widgets_display( $instance ) {

	$instance = atomic_widgets_init_instance( $instance );

	/* check */
	if ( $instance['atomic_context'] ) {

		/* if in conditional, display it */
		if ( atomic_widget_conditional( $instance['atomic_context'] ) )
			return $instance;

		/* if not, don't display the widget */
		else
			return false;
	}

	return $instance;
}


/**
 * Initializes widget instance
 *
 * @since 0.1.0
 */
function atomic_widgets_init_instance( $instance ) {

	if ( !isset( $instance['atomic_context'] ) ) {
		$instance['atomic_context'] = '';
	}

	return $instance;
}


/**
 * Atomic Context Conditional 
 * 
 * @param $needles	atomic context target
 * @since 0.1.0
 */
function atomic_widget_conditional( $needles ){

	/* return if not supported */
	if( !function_exists('hybrid_get_context') )
		return true;

	/* default is "false" */
	$out = false;

	/* if empty set to true */
	if ( empty( $needles ) )
		$out = true;

	/* if it's not an array */
	if ( !is_array( $needles ) )
		$needles = explode( ',', $needles ); // make it an array

	/* current page context: "the haystack" */
	$contexts = array();
	if( function_exists('hybrid_get_context') )
		$contexts = hybrid_get_context();

	/* foreach needles */
	foreach ( $needles as $needle ){

		/* trim it */
		$needle = trim( $needle );

		/* if needle if found */
		if ( in_array( $needle, $contexts ) )
			$out = true;

		/* if needles have "!" */
		if ( strpos( $needle, '!' ) !== false ) {

			/* remove "!" char */
			$clean_needle = trim( str_replace ( '!', '', $needle ) );

			/* if excluded set it to false */
			if ( in_array( $clean_needle, $contexts ) )
				$out = false;
		}
	}
	/* output */
	return $out;
}