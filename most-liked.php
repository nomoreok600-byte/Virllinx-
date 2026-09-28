<?php
$listing_title = 'Most Liked Videos';
$order_by = 'v.likes DESC, v.views DESC';
$listing_base_url = '/most-liked';
require __DIR__ . '/includes/listing.php';
