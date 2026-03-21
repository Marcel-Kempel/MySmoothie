<?php

declare(strict_types=1);

// Grundlegende Textbereinigung für Form- und API-Eingaben.
function clean_text_field(mixed $value, int $maxLength = 255): string
{
    $text = trim((string) $value);
    if ($maxLength > 0 && strlen($text) > $maxLength) {
        $text = substr($text, 0, $maxLength);
    }

    return $text;
}

// Validiert und normalisiert die Registrierungsdaten aus dem Formular.
function validate_registration_input(array $input): array
{
    $values = [
        'first_name' => clean_text_field($input['first_name'] ?? '', 100),
        'last_name' => clean_text_field($input['last_name'] ?? '', 100),
        'address' => clean_text_field($input['address'] ?? '', 255),
        'email' => strtolower(clean_text_field($input['email'] ?? '', 190)),
    ];

    $password = (string) ($input['password'] ?? '');
    $passwordConfirm = (string) ($input['password_confirm'] ?? '');

    $errors = [];

    if ($values['first_name'] === '') {
        $errors['first_name'] = 'Vorname ist erforderlich.';
    }

    if ($values['last_name'] === '') {
        $errors['last_name'] = 'Nachname ist erforderlich.';
    }

    if ($values['address'] === '') {
        $errors['address'] = 'Adresse ist erforderlich.';
    }

    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Passwort muss mindestens 8 Zeichen haben.';
    }

    if ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Passwort und Bestätigung stimmen nicht überein.';
    }

    return [
        'values' => $values,
        'password' => $password,
        'errors' => $errors,
    ];
}

// Validiert und normalisiert die Login-Daten.
function validate_login_input(array $input): array
{
    $values = [
        'email' => strtolower(clean_text_field($input['email'] ?? '', 190)),
    ];
    $password = (string) ($input['password'] ?? '');

    $errors = [];

    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
    }

    if ($password === '') {
        $errors['password'] = 'Passwort ist erforderlich.';
    }

    return [
        'values' => $values,
        'password' => $password,
        'errors' => $errors,
    ];
}

// Validiert ID-Listen aus JSON (nur positive, eindeutige, sortierte IDs).
function validate_id_list(mixed $raw): array
{
    if (!is_array($raw)) {
        return [];
    }

    $result = [];
    foreach ($raw as $value) {
        $id = (int) $value;
        if ($id > 0) {
            $result[] = $id;
        }
    }

    $result = array_values(array_unique($result));
    sort($result);

    return $result;
}

// Liefert einen sicheren, begrenzten Anzeigenamen für die Konfiguration.
function validate_configuration_name(mixed $raw): string
{
    $name = clean_text_field($raw, 120);
    if ($name === '') {
        $name = 'Mein Smoothie';
    }

    return $name;
}
