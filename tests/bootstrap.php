<?php

declare(strict_types=1);

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoloadPath)) {
    require_once $autoloadPath;
}

require_once __DIR__ . '/../includes/business_logic.php';
require_once __DIR__ . '/../includes/validation_utils.php';
require_once __DIR__ . '/../includes/email_utils.php';
require_once __DIR__ . '/../includes/token_utils.php';
require_once __DIR__ . '/../includes/item_heal_helpers.php';
require_once __DIR__ . '/../includes/enigmes_progression.php';
