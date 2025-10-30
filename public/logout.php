<?php
require_once __DIR__ . '/../src/helpers.php';
start_session();
session_unset();
session_destroy();
redirect('index.php');