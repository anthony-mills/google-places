<?php

function setup_postdata($single) {
	global $post;

	$post = $single;
}

function setup_reviewdata($single) {
	global $review;

	$review = $single;
}

function the_name() {
	global $post;

	echo $post['name'];
}

function the_icon() {
	global $post;

	echo $post['icon'];
}

function the_types($front=false, $back=false) {
	global $post;
	if ($front && $back) {
		$output = $front . implode($back . $front, $post['types']) . $back;
	} else {
		$output = implode(', ', $post['types']);
	}
	echo $output;
}

function the_lat_long() {
	global $post;

	echo $post['geometry']['location']['lat'] . ' ' . $post['geometry']['location']['lat'];
}

function the_vicinity() {
	global $post;

	echo $post['vicinity'];
}

function the_photos( $max_height=false, $max_width=false ) {
	global $post;
	$style = '';

	if ( !isset( $post['photos'])) {
		return;	
	}
	
	if ( $max_height ) {
		$style = "max-height: $max_height; max-width: $max-width";
	}

	$output = array();
	foreach( $post['photos'] as $photo ) {
		$output[] = "<img src='' style='$style'/>";
	}
}

function the_photo_count() {
	global $post;

	if ( !isset( $post['photos'])) {
		$count = 0;
	} else {
		$count = sizeof( $post['photos']);
	}

	echo $count;
}

function the_rating() {
	global $post;

	if ( !isset( $post['rating'])) {
		return;
	}

	echo $post['rating'];
}

function the_open_now() {
	global $post;

	if ( !isset( $post['opening_hours'] ) || !isset( $post['opening_hours']['open_now'] ) ) {
		return;
	}

	echo ( $post['opening_hours']['open_now'] == 'false' ) ? 'Closed' : 'Open';
}


function the_price_level() {
	global $post;

	if ( !isset( $post['price_level'] ) ) {
		return;
	}


	switch( $post['price_level'] ) {
		case 0:
			$price = 'Free';
			break;
		case 1:
			$price = 'Inexpensive';
			break;
		case 2:
			$price = 'Moderate';
			break;
		case 3:
			$price = 'Expensive';
			break;
		case 4:
			$price = 'Very Expensive';
			break;

	}

	echo $price;
}

function the_id() {
	global $post;

	echo $post['place_id'];
}

function the_span_address() {
	global $post;

	echo $post['adr_address'];
}

function the_address() {
	global $post;

	echo $post['formatted_address'];
}

function the_phone() {
	global $post;

	echo $post['formatted_phone_number'];
}

function the_review_author() {
	global $review;

	echo $review['author_name'];
}

function the_review_rating() {
	global $review;

	echo $review['rating'];
}


function the_review_text() {
	global $review;

	echo $review['text'];
}

function the_website() {
	global $post;

	echo $post['website'];
}

function the_user_ratings_total() {
	global $post;

	echo $post['user_ratings_total'];
}