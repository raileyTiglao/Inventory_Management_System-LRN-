<?php
/**
 * General-purpose helpers for the IMS front end.
 */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function is_active(string $page): string
{
    global $activePage;
    return ($activePage ?? '') === $page ? 'active' : '';
}
