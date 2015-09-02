<?php

require_once __DIR__."/class.feeder.php";

$feed_url = filter_input(INPUT_GET, "feed_url", FILTER_VALIDATE_URL);

$callback = filter_input(INPUT_GET, "callback", FILTER_SANITIZE_STRING);

$f = new Feeder($feed_url, $callback);

?>