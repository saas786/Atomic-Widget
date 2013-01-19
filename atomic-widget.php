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
	add_filter( 'widget_display_callback', 'atomic_widgets_display', 10, 3 );

	/* update widget instance */
	add_filter( 'widget_update_callback', 'atomic_widgets_update', 10, 2 );

}

/**
 * Display the form at the bottom of each widget.
 *
 * @since 0.1.0
 */
function atomic_widgets_form( $widget, $return, $instance ){

	if ( $return == 'noform') {
		$return = true;
	}

	/* widget id */
	$widget_id = $widget->id;

	/* get instance */
	$instance = atomic_widgets_init_instance( $widget_id, $instance );

	/* HTML Form */
	?>
	<p><label for="atomic_context-<?php echo $widget_id; ?>">Atomic Context:</label><textarea class="widefat" rows="2" id="atomic_context-<?php echo $widget_id; ?>" name="atomic_context"><?php echo $instance['atomic_context']; ?></textarea></p>

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
	$instance['atomic_context'] = trim( strip_tags( $_POST['atomic_context'] ) );

	return $instance;
}

/**
 * Front end display output.
 *
 * @since 0.1.0
 */
function atomic_widgets_display( $instance, $widget, $args ) {

	/* widget id */
	$widget_id = $widget->id;

	/* instance */
	$instance = atomic_widgets_init_instance( $widget_id, $instance );

	/* check */
	if ( $instance['atomic_context'] ) {

		/* if in conditional, display it */
		if ( atomic_widget_conditional( $instance['atomic_context'] ) )
			$instance = $instance;

		/* if not, don't display the widget */
		else
			$instance = false;
	}

	/* atomic filter, to easily disable the widgets using atomic context */
	if( function_exists('apply_atomic') )
		$instance = apply_atomic( 'atomic_widgets_display_' . $widget_id, $instance );

	return $instance;
}


/**
 * Initializes widget instance
 *
 * @since 0.1.0
 */
function atomic_widgets_init_instance( $widget_id, $instance ) {

	/* if it's not set, set it. */
	if ( !isset( $instance['atomic_context'] ) ) {
		$instance['atomic_context'] = '';
	}

	/* atomic filter, to easily modify input */
	if( function_exists('apply_atomic') )
		$instance['atomic_context'] = apply_atomic( 'atomic_widgets_' . $widget_id, $instance['atomic_context'] );

	return $instance;
}


/**
 * Atomic Context Conditional 
 * 
 * @param $targets	mixed	atomic context target (the needles)
 * @since 0.1.0
 */
function atomic_widget_conditional( $targets ){

	/* return "true" if not supported to display the widget */
	if( !function_exists('hybrid_get_context') )
		return true;

	/* default is "false" */
	$out = false;

	/* if empty set to "true" */
	if ( empty( $targets ) )
		$out = true;

	/* if it's not an array, make it an array */
	if ( !is_array( $targets ) )
		$targets = explode( ',', $targets );

	/* current page context (the haystack) */
	$contexts = array();
	if( function_exists('hybrid_get_context') )
		$contexts = hybrid_get_context();

	/* add "wp" in context to display in all context */
	$contexts[] = "wp"; // so we can exclude better.

	/* foreach needles */
	foreach ( $targets as $target ){

		/* trim it */
		$target = trim( $target );

		/* if target context is found */
		if ( in_array( $target, $contexts ) )
			$out = true;

		/* if target have "!" */
		if ( strpos( $target, '!' ) !== false ) {

			/* remove "!" char */
			$clean_target = trim( str_replace ( '!', '', $target ) );

			/* if excluded set it to false */
			if ( in_array( $clean_target, $contexts ) )
				$out = false;
		}
	}
	/* output */
	return $out;
}