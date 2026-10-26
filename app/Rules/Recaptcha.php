<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class Recaptcha implements Rule
{
    public function passes($attribute, $value)
    {
        // Reuse the configured CAPTCHA secret; never embed credentials in source code.
        $secret = config('recaptchav3.secret');
        if (!$secret || !is_string($value) || $value === '') {
            return false;
        }
        try {
            $response = Http::asForm()->withOptions(['connect_timeout' => 5])->timeout(15)
                ->post('https://www.google.com/recaptcha/api/siteverify', ['secret' => $secret, 'response' => $value]);
        } catch (ConnectionException $exception) {
            return false;
        }
        return $response->successful() && $response->json('success') === true
            && (float) $response->json('score', 0) > 0.7;
    }

    public function message()
    {
        return 'La validación de reCAPTCHA está fallando.';
    }
}
