<?php
/**
 * Server-side rendering for the Recent Ecents showcase block
 * This inherits a lot of logic from the events block, yet due to time constraints
 * does not benefit from any inheritance or shared methods
 *
 * @since 	1.1.7
 * @package Milieux Blocks
 */


/**
 * Renders the post grid block on server.
 */
function milieux_blocks_render_block_core_latest_events( $attributes ) {

	if (function_exists('milieux_get_events')) {
		$recent_posts = milieux_get_events($attributes['displayPastPosts']);
	} else {
		return;
	}

	// print_r($attributes);
	$main_event_markup = '';
	$list_items_markup = '';

	$mainEvent = $attributes['mainEvent']; // Array

	// Check if a featured Post is set and toggled
	if ($attributes['displayMainEvent'] == true && is_array($mainEvent)) {
		$fp_ID = $mainEvent['id'];
		$fp_img = $mainEvent['image'];

		$main_event_markup .= sprintf(
			'<div class="mlx-events__container %1$s"><div class="mlx-main-event">',
			($attributes['displayMainEvent'] == true) ? 'has-main-event' : ''
		);

		$main_event_markup .= sprintf(
			'<noscript><img= src="%1$s"/></noscript>',
			$fp_img['source_url']
		);

		$main_event_markup .= sprintf(
			'<div class="mlx-main-event__image" style="padding-bottom:%1$s%%">',
			_mlx__get_image_ratio_padding ($fp_img['id'])
		);

		$main_event_markup .= sprintf(
			'<img class="main-event-image hero lazyload" src="%1$s" ',
			$fp_img['source_url']
		);

		$main_event_markup .= sprintf(
			'srcset="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-srcset="%1$s" ',
			wp_get_attachment_image_srcset($fp_img['id'])
		);

		$main_event_markup .= sprintf(
			'data-sizes="auto" alt="%1$s"></div>',
			$fp_img['alt_text']
		);

		// Add title and open meta section
		$main_event_markup .= sprintf(
			'<div class="mlx-main-event__meta"><a href="%1$s"><h2 class="mlx-main-event__title">%2$s</h2></a>',
			esc_html($mainEvent['url']),
			esc_html($mainEvent['title'])
		);

		$main_event_markup .= sprintf(
			'<div class="mlx-main-event__date mlx-t-number">%1$s</div>',
			esc_html($mainEvent['acf']['event_date'])
		);

		$main_event_markup .= sprintf( '</div></div>'); // close main event div
	}


	if (is_array($recent_posts)) {

		foreach ( $recent_posts as $post ) {
			// Get the post ID
			$post_id = $post['ID'];

			// Get the post thumbnail
			$post_thumb_id = get_post_thumbnail_id( $post_id );

			if ( $post_thumb_id && isset( $attributes['displayPostImage'] ) && $attributes['displayPostImage'] ) {
				$post_thumb_class = 'has-thumb';
			} else {
				$post_thumb_class = 'no-thumb';
			}

			// Start the markup for the post
			$list_items_markup .= sprintf(
				'<article class="%1$s">',
				esc_attr( $post_thumb_class )
			);

			// Wrap the text content
			$list_items_markup .= sprintf(
				'<div class="mlx-block-events__text">'
			);

				// Get the post title
				$title = get_the_title( $post_id );

				if ( ! $title ) {
					$title = __( 'Untitled', 'milieux-blocks' );
				}

				$list_items_markup .= sprintf(
					'<h2 class="mlx-events__title entry-title"><a href="%1$s" rel="bookmark">%2$s</a></h2>',
					esc_url( get_permalink( $post_id ) ),
					esc_html( $title )
				);

				$list_items_markup .= sprintf(
					'<time datetime="%1$s" class="mlx-events__date mlx-t-number">%2$s</time>',
					esc_attr( DateTime::createFromFormat('Ymd', $post['event_date'] )->format("Y-m-d H:i:s e") ), // UTC!
					esc_html( DateTime::createFromFormat('Ymd', $post['order'] )->format("M d") )
				);

				// Wrap the excerpt content
				$list_items_markup .= sprintf(
					'<div class="mlx-events__excerpt">'
				);

					// Get the excerpt
					$excerpt = apply_filters( 'the_excerpt', get_post_field( 'post_excerpt', $post_id, 'display' ) );

					if( empty( $excerpt ) ) {
						$excerpt = apply_filters( 'the_excerpt', wp_trim_words( $post['event_content'], 25 ) );
					}

					if ( ! $excerpt ) {
						$excerpt = null;
					}

					if ( isset( $attributes['displayPostExcerpt'] ) && $attributes['displayPostExcerpt'] ) {
						$list_items_markup .=  wp_kses_post( $excerpt );
					}

				// Close the excerpt content
				$list_items_markup .= sprintf(
					'</div>'
				);

			// Wrap the text content
			$list_items_markup .= sprintf(
				'</div>'
			);

			// Close the markup for the post
			$list_items_markup .= "</article>\n";
		}
	}

	// Build the classes
	$class = "mlx-events align{$attributes['align']}";

	if ( isset( $attributes['className'] ) ) {
		$class .= ' ' . $attributes['className'];
	}

	$grid_class = 'mlx-events__items';

	if ( isset( $attributes['postLayout'] ) && 'list' === $attributes['postLayout'] ) {
		$grid_class .= ' is-list';
	} else {
		$grid_class .= ' is-grid';
	}

	if ( isset( $attributes['columns'] ) && 'grid' === $attributes['postLayout'] ) {
		$grid_class .= ' columns-' . $attributes['columns'];
	}

	// Output the post markup
	$block_content = sprintf(
		'<div class="%1$s">%2$s<div class="%3$s"><h3 class="page-type-title">Events</h3>%4$s</div></div></div>',
		esc_attr( $class ),
		$main_event_markup,
		esc_attr( $grid_class ),
		$list_items_markup
	);

	return $block_content;
}

/**
 * Registers the `core/latest-events` block on server.
 */
function milieux_blocks_register_block_core_latest_events() {

	// Check if the register function exists
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type( 'milieux-blocks/events', array(
		'attributes' => array(
			'categories' => array(
				'type' => 'string',
			),
			'className' => array(
				'type' => 'string',
			),
			'postsToShow' => array(
				'type' => 'number',
				'default' => 6,
			),
			'displayPostAuthor' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'displayPostImage' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'displayPostLink' => array(
				'type' => 'boolean',
				'default' => true,
			),
			'displayPastPosts' => array(
				'type' => 'boolean',
				'default' => false,
			),
			'postLayout' => array(
				'type' => 'string',
				'default' => 'grid',
			),
			'columns' => array(
				'type' => 'number',
				'default' => 2,
			),
			'align' => array(
				'type' => 'string',
				'default' => 'center',
			),
			'width' => array(
				'type' => 'string',
				'default' => 'wide',
			),
			'order' => array(
				'type' => 'string',
				'default' => 'desc',
			),
			'orderBy'  => array(
				'type' => 'string',
				'default' => 'date',
			),
			'mainEvent' => array(
				'type' => 'object',
				'default' => 0,
			),
			'displayMainEvent' => array(
				'type' => 'boolean',
				'default' => false,
			)
		),
		'render_callback' => 'milieux_blocks_render_block_core_latest_events',
	) );
}

add_action( 'init', 'milieux_blocks_register_block_core_latest_events' );

/**
 * Computes date string for a given event
 * @since Milieux Blocks 1.0.12
 *
 * @param	 {int}    the given post id (required)
 * @return {string} a formatted date string
 */
function milieux_blocks_render_event_date( $event ) {
	if (!isset($event)) { return; }

	$type = $event['event_type']; // contains a string of the event type
	$meta = $event['event_meta']; // contains a variable multidimensional array of properties
	$date = '';

	if ($type == 'single') {
		$date = esc_html( DateTime::createFromFormat('Ymd', $event['order'] )->format("M d") );
	}

	if ( $type == 'multi' ) {

	}

}
