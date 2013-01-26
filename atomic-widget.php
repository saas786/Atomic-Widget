<?php
/**
 * Plugin Name: Atomic Widget
 * Plugin URI: http://shellcreeper.com/portfolio/item/atomic-widget/
 * Description: This plugin gives every widget an extra control field called "Atomic Widget" that lets you control the pages that the widget will appear on. The text field lets you use <a href="http://themehybrid.com/docs/tutorials/hybrid-core-context">Hybrid Core Atomic Context</a> by <a href="http://justintadlock.com/">Justin Tadlock</a>.
 * Version: 0.1.3
 * Author: David Chandra Purnama
 * Author URI: http://shellcreeper.com/
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms  
 * of the GNU General Public License version 2, as published by the Free Software Foundation.
 * You may NOT assume that you can use any other version of the GPL.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @version 0.1.3
 * @author David Chandra Purnama <david.warna@gmail.com>
 * @copyright Copyright (c) 2013, David Chandra Purnama
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */


/* Version */
define( 'ATOMIC_WIDGET_VERSION', '0.1.3' );


/* Hooks to 'init', plugins loaded is too soon?  */
add_action( 'init','atomic_widget_setup' );


/**
 * Setup on plugins loaded hook
 * 
 * @since 0.1.0
 */
function atomic_widget_setup(){

	/* create form in each widgets */
	add_action( 'in_widget_form', 'atomic_widget_form', 10, 3 );

	/* front end display output */
	add_filter( 'widget_display_callback', 'atomic_widget_display', 10, 3 );

	/* unset the widget from sidebar */
	add_filter( 'sidebars_widgets', 'widget_atomic_filter_sidebars_widgets', 10 );

	/* update widget instance */
	add_filter( 'widget_update_callback', 'atomic_widget_update', 10, 2 );

	/* bulk modify context using PHP 5.3 anonymous function */
	if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
		require_once( trailingslashit( plugin_dir_path( __FILE__) ) . 'bulk-filter.php' );
	}

	/* $atomic_widget_context global */
	add_action( 'template_redirect', 'atomic_widget_context' );
}


/**
 * Display the form at the bottom of each widget.
 *
 * @since 0.1.0
 */
function atomic_widget_form( $widget, $return, $instance ){

	if ( $return == 'noform') {
		$return = true;
	}

	/* widget id */
	$widget_id = $widget->id;

	/* display widget id */
	$display_id = '<apan style="color:#037d12">' . $widget_id . '</span>';

	/* get instance */
	$instance = atomic_widget_init_instance( $widget_id, $instance );

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
function atomic_widget_update( $new_instance, $old_instance ) {

	$instance = $new_instance;

	/* sanitize input */
	$atomic_context = trim( strip_tags( $_POST['atomic_context'] ) ); // stip tags, and trim it

	$instance['atomic_context'] = $atomic_context;

	return $instance;
}


/**
 * Front end display output.
 *
 * this will return false for widget output.
 *
 * @since 0.1.0
 */
function atomic_widget_display( $instance, $widget, $args ) {

	/* widget id */
	$widget_id = $widget->id;

	/* instance */
	$instance = atomic_widget_init_instance( $widget_id, $instance );

	/* check */
	if ( $instance['atomic_context'] ) {

		/* if in conditional, display it */
		if ( atomic_widget_conditional( $instance['atomic_context'] ) )
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
 * Disable widgets in sidebar
 * 
 * unset the widget so sidebar will inactive instead just removing the widgets
 * 
 * @since 0.1.1
 */
function widget_atomic_filter_sidebars_widgets( $sidebars_widgets ){

	/* filter for theme that have default widget if sidebar is inactive */
	$atomic_widget_disable_sidebar = apply_filters( 'atomic_widget_disable_sidebar', true );

	/* only in front end */
	if ( !is_admin() && $atomic_widget_disable_sidebar == true ){

		/* globalize registered widget controls to get instance */
		global $wp_registered_widget_controls;

		/* simplyfy */
		$controls = $wp_registered_widget_controls;

		/* get each sidebar / widget area */
		foreach( $sidebars_widgets as $widget_area => $widget_list ){

			/* get all widget list in the area */
			foreach( $widget_list as $pos => $widget_id ){

				/* get widget option name */
				$widget_option_name = $controls[$widget_id]["callback"][0]->option_name;

				/* get widget number, e.g: widget meta-2 number is 2 */
				$widget_number = $controls[$widget_id]["params"][0]["number"];

				/* get option for the widget */
				$widgets_option = get_option( $widget_option_name );

				/* get atomic context widget instance */
				$atomic_instance = $widgets_option[$widget_number]["atomic_context"];

				/* unset the widget from sidebar/widget area */
				if ( ! atomic_widget_conditional( $atomic_instance ) ){
					unset( $sidebars_widgets[$widget_area][$pos] );
				}
			}
		}
	}

	return $sidebars_widgets;
}


/**
 * Initializes widget instance
 *
 * @since 0.1.0
 */
function atomic_widget_init_instance( $widget_id, $instance ) {

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
function atomic_widget_conditional( $targets ){

	/* default is "false" */
	$out = false;

	/* if empty set to "true" to display widget in all context */
	if ( empty( $targets ) )
		$out = true;

	/* if it's not an array, make it an array */
	if ( !is_array( $targets ) )
		$targets = explode( ',', $targets );

	/* current page context (the haystack) */
	$contexts = array();

	/* add context. */
	$contexts = apply_filters( 'hybrid_context', atomic_widget_context() );

	/* add "wp" in context to display widget in all context */
	$contexts[] = "wp"; // so we can exclude better.

	/* contexts filter, add widget context without add it in `hybrid_context` */
	$contexts = array_map( 'esc_attr', apply_filters( 'atomic_widget_context', $contexts ) );

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
	return apply_filters( 'atomic_widget_conditional', $out );
}



/**
 * Atomic Widget Context Alternate 
 * 
 * Create atomic context when not using hybrid core theme.
 * just a duplicate from hybrid_get_context() function from Hybrid Core
 * So we can use it for other theme.
 * 
 * @author		Justin Tadlock <justin@justintadlock.com>
 * @copyright	Copyright (c) 2008 - 2013, Justin Tadlock
 * @link		http://themehybrid.com/hybrid-core
 * @link		http://themehybrid.com/docs/tutorials/hybrid-core-context
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @since		0.1.3
 */
function atomic_widget_context(){

	global $atomic_widget_context;

	/* If $atomic_widget_context has been set. Just return the variable. */
	if ( isset( $atomic_widget_context ) )
		return $atomic_widget_context;

	/* Set some variables for use within the function. */
	$atomic_widget_context = array();
	$object = get_queried_object();
	$object_id = get_queried_object_id();

	/* Front page of the site. */
	if ( is_front_page() )
		$atomic_widget_context[] = 'home';

	/* Blog page. */
	if ( is_home() ) {
		$atomic_widget_context[] = 'blog';
	}

	/* Singular views. */
	elseif ( is_singular() ) {
		$atomic_widget_context[] = 'singular';
		$atomic_widget_context[] = "singular-{$object->post_type}";
		$atomic_widget_context[] = "singular-{$object->post_type}-{$object_id}";
	}

	/* Archive views. */
	elseif ( is_archive() ) {
		$atomic_widget_context[] = 'archive';

		/* Post type archives. */
		if ( is_post_type_archive() ) {
			$post_type = get_post_type_object( get_query_var( 'post_type' ) );
			$atomic_widget_context[] = "archive-{$post_type->name}";
		}

		/* Taxonomy archives. */
		if ( is_tax() || is_category() || is_tag() ) {
			$atomic_widget_context[] = 'taxonomy';
			$atomic_widget_context[] = "taxonomy-{$object->taxonomy}";

			$slug = ( ( 'post_format' == $object->taxonomy ) ? str_replace( 'post-format-', '', $object->slug ) : $object->slug );
			$atomic_widget_context[] = "taxonomy-{$object->taxonomy}-" . sanitize_html_class( $slug, $object->term_id );
		}

		/* User/author archives. */
		if ( is_author() ) {
			$user_id = get_query_var( 'author' );
			$atomic_widget_context[] = 'user';
			$atomic_widget_context[] = 'user-' . sanitize_html_class( get_the_author_meta( 'user_nicename', $user_id ), $user_id );
		}

		/* Date archives. */
		if ( is_date() ) {
			$atomic_widget_context[] = 'date';

			if ( is_year() )
				$atomic_widget_context[] = 'year';

			if ( is_month() )
				$atomic_widget_context[] = 'month';

			if ( get_query_var( 'w' ) )
				$atomic_widget_context[] = 'week';

			if ( is_day() )
				$atomic_widget_context[] = 'day';
		}

		/* Time archives. */
		if ( is_time() ) {
			$atomic_widget_context[] = 'time';

			if ( get_query_var( 'hour' ) )
				$atomic_widget_context[] = 'hour';

			if ( get_query_var( 'minute' ) )
				$atomic_widget_context[] = 'minute';
		}
	}

	/* Search results. */
	elseif ( is_search() ) {
		$atomic_widget_context[] = 'search';
	}

	/* Error 404 pages. */
	elseif ( is_404() ) {
		$atomic_widget_context[] = 'error-404';
	}

	return $atomic_widget_context;
}