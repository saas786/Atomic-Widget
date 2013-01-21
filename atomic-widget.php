<?php
/**
 * Plugin Name: Atomic Widget
 * Plugin URI: http://shellcreeper.com/
 * Description: Atomic Widget lets you control on which pages widgets appear using Hybrid Core Atomic Context. It only works for theme powered by <a href="http://themehybrid.com/hybrid-core">Hybrid Core</a>.
 * Version: 0.1.0-beta
 * Author: David Chandra Purnama
 * Author URI: http://shellcreeper.com/
 *
 * This plugin is similar to widget logic plugin by Alan Trewartha but alot simpler.
 * And it uses Hybrid Core - Atomic Context by Justin Tadlock.
 * 
 * Available filter hook:
 * 1. atomic_widget_display_{widget-id}
 * 		filter to modify output for each widget (bool, true/false)
 * 2. atomic_widget_{widget-id}
 * 		filter to modify "atomic_context" instance input for each widgets (string)
 * 3. atomic_widgets_contexts
 * 		filter to modify contexts without adding it to hybrid core contexts. (array)
 * 4. atomic_widgets_conditional
 * 		filter to modify conditional output (bool, true/false) 
 * 5. atomic_widgets (require PHP 5.3)
 * 		bulk filter to modify each widgets in one array.
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

/**
 * Setup on plugins loaded hook
 * 
 * @since 0.1.0
 */
function atomic_widget_setup(){

	/* create form in each widgets */
	add_action( 'in_widget_form', 'atomic_widgets_form', 10, 3 );

	/* front end display output */
	add_filter( 'widget_display_callback', 'atomic_widgets_display', 10, 3 );

	/* update widget instance */
	add_filter( 'widget_update_callback', 'atomic_widgets_update', 10, 2 );

	/* bulk modify context using PHP 5.3 anonymous function */
	if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
		require_once( trailingslashit( plugin_dir_path( __FILE__) ) . 'bulk-filter.php' );
	}
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

	/* display widget id */
	$display_id = '<apan style="font-weight:bold;color:#037d12">' . $widget_id . '</span>';

	/* get instance */
	$instance = atomic_widgets_init_instance( $widget_id, $instance );

	/* HTML Form */
	?>
	<p><label for="atomic_context-<?php echo $widget_id; ?>">Atomic Context: <?php echo $display_id; ?></label><textarea class="widefat" rows="2" id="atomic_context-<?php echo $widget_id; ?>" name="atomic_context"><?php echo $instance['atomic_context']; ?></textarea></p>

	<?php
}


/**
 * Save Instance.
 *
 * @since 0.1.0
 */
function atomic_widgets_update( $new_instance, $old_instance ) {

	$instance = $new_instance;

	/* sanitize input */
	$atomic_context = trim( strip_tags( $_POST['atomic_context'] ) ); // stip tags, and trim it

	$instance['atomic_context'] = $atomic_context;

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
		if ( atomic_widgets_conditional( $instance['atomic_context'] ) )
			$instance = $instance;

		/* if not, don't display the widget */
		else
			$instance = false;
	}

	/* filter, to easily disable widgets */
	$instance = apply_filters( 'atomic_widget_display_' . $widget_id, $instance );

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

	/* filter, to modify input */
	$instance['atomic_context'] = apply_filters( 'atomic_widget_' . $widget_id, $instance['atomic_context'] );

	return $instance;
}


/**
 * Atomic Context Conditional 
 * 
 * @param $targets	mixed	atomic context target (the needles)
 * @since 0.1.0
 */
function atomic_widgets_conditional( $targets ){

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

	/* in hybrid code themes, add core context. */
	if( function_exists('hybrid_get_context') )
		$contexts = hybrid_get_context();

	/* in non hybrid code themes, add alternate context. */
	else
		$contexts = atomic_widgets_context_alt();

	/* add "wp" in context to display in all context */
	$contexts[] = "wp"; // so we can exclude better.

	/* contexts filter, add widget context without add it in hybrid_get_context */
	$contexts = array_map( 'esc_attr', apply_filters( 'atomic_widgets_contexts', $contexts ) );
	
	/* make sure each context is unique */
	$contexts = array_unique( $contexts );

	/* foreach targets */
	foreach ( $targets as $target ){

		/* sanitize */
		$target = trim( strip_tags( $target ) );
		$target = str_replace(' ', '', $target); // remove spaces

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
	return apply_filters( 'atomic_widgets_conditional', $out );
}


/**
 * Atomic Alternate Context 
 * 
 * Alternate Context when not using hybrid core theme.
 * just a duplicate from hybrid_get_context() function from Hybrid Core
 * 
 * @author		Justin Tadlock <justin@justintadlock.com>
 * @copyright	Copyright (c) 2008 - 2013, Justin Tadlock
 * @link		http://themehybrid.com/hybrid-core
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @since		0.1.0
 */
function atomic_widgets_context_alt(){

	/* Set some variables for use within the function. */
	$contexts = array();
	$object = get_queried_object();
	$object_id = get_queried_object_id();

	/* Front page of the site. */
	if ( is_front_page() )
		$contexts[] = 'home';

	/* Blog page. */
	if ( is_home() ) {
		$contexts[] = 'blog';
	}

	/* Singular views. */
	elseif ( is_singular() ) {
		$contexts[] = 'singular';
		$contexts[] = "singular-{$object->post_type}";
		$contexts[] = "singular-{$object->post_type}-{$object_id}";
	}

	/* Archive views. */
	elseif ( is_archive() ) {
		$contexts[] = 'archive';

		/* Post type archives. */
		if ( is_post_type_archive() ) {
			$post_type = get_post_type_object( get_query_var( 'post_type' ) );
			$contexts[] = "archive-{$post_type->name}";
		}

		/* Taxonomy archives. */
		if ( is_tax() || is_category() || is_tag() ) {
			$contexts[] = 'taxonomy';
			$contexts[] = "taxonomy-{$object->taxonomy}";

			$slug = ( ( 'post_format' == $object->taxonomy ) ? str_replace( 'post-format-', '', $object->slug ) : $object->slug );
			$contexts[] = "taxonomy-{$object->taxonomy}-" . sanitize_html_class( $slug, $object->term_id );
		}

		/* User/author archives. */
		if ( is_author() ) {
			$user_id = get_query_var( 'author' );
			$contexts[] = 'user';
			$contexts[] = 'user-' . sanitize_html_class( get_the_author_meta( 'user_nicename', $user_id ), $user_id );
		}

		/* Date archives. */
		if ( is_date() ) {
			$contexts[] = 'date';

			if ( is_year() )
				$contexts[] = 'year';

			if ( is_month() )
				$contexts[] = 'month';

			if ( get_query_var( 'w' ) )
				$contexts[] = 'week';

			if ( is_day() )
				$contexts[] = 'day';
		}

		/* Time archives. */
		if ( is_time() ) {
			$contexts[] = 'time';

			if ( get_query_var( 'hour' ) )
				$contexts[] = 'hour';

			if ( get_query_var( 'minute' ) )
				$contexts[] = 'minute';
		}
	}

	/* Search results. */
	elseif ( is_search() ) {
		$contexts[] = 'search';
	}

	/* Error 404 pages. */
	elseif ( is_404() ) {
		$contexts[] = 'error-404';
	}

	return $contexts;
}