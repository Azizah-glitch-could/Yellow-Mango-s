<?php
require __DIR__ . '/lib.php';
require_post();
out(['order' => order_view(auth_order(body()))]);
