<?php
$listing_title = 'Popular Videos';
$order_by = 'v.views DESC, v.id DESC';
$listing_base_url = '/popular';
require __DIR__ . '/includes/listing.php';
