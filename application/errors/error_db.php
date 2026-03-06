<?php 
$message = preg_replace("/<[^>]+>/", '', $message);
echo("DB ERROR: (database) $message\n");
