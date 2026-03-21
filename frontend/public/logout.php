<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/app/bootstrap.php';

// Logout-Flow: Session löschen, Erfolgsmeldung setzen, zur Landing Page zurück.
logout_user();
flash_set('success', 'Du wurdest erfolgreich abgemeldet.');
redirect('index.php');
