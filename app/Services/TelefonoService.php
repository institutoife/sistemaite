<?php

namespace App\Services;

class TelefonoService
{
    public function isMissing(?string $phone): bool
    {
        $phone = trim((string) $phone);
        return $phone === '' || (bool) preg_match('/^0+$/', $phone);
    }

    public function normalize(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '' || !preg_match('/^\+?[0-9\s().-]+$/', $phone)) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $phone);
        if (strpos($digits, '00') === 0) {
            $digits = substr($digits, 2);
            $phone = '+'.$digits;
        }
        if (strlen($digits) === 8 && $phone[0] !== '+' && preg_match('/^[23467]/', $digits)) {
            return '+591'.$digits;
        }
        if (preg_match('/^591[23467]\d{7}$/', $digits)) {
            return '+'.$digits;
        }
        if ($phone[0] === '+' && strpos($digits, '591') !== 0 && preg_match('/^[1-9]\d{7,14}$/', $digits)) {
            return '+'.$digits;
        }
        return null;
    }
}
