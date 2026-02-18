<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/app/bootstrap.php';

logout_user();
flash_set('success', 'Du wurdest erfolgreich abgemeldet.');
redirect('index.php');