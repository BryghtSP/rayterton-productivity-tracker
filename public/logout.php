<?php
require_once __DIR__ . '/../lib/auth.php';
session_destroy();
header("Location: https://raytertonapps.com/prodtracker/public/");