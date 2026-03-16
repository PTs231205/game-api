<?php
/**
 * Launch API - direct URL: /v1/launch.php?api_key=...&user_id=...&game_uid=...
 * Nginx .php file ko run karta hai, isliye rewrite ki zaroorat nahi.
 */
require __DIR__ . '/../api/v1/launch.php';
