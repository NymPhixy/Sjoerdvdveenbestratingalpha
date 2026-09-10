<?php

require_once '../app/functions/auth.php';

logout();

header('Location: login.php');
exit;