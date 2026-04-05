<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use ReCaptcha\ReCaptcha as GoogleReCaptcha;

class ReCaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.recaptcha.secret_key');

        if (empty($value)) {
            $fail('The reCAPTCHA token is missing.');
            return;
        }

        $recaptcha = new GoogleReCaptcha($secretKey);
        $resp = $recaptcha->verify($value, request()->ip());

        if (!$resp->isSuccess()) {
            $fail('reCAPTCHA validation failed. Please try again.');
        }
        
        // Optional: Check score for v3
        // if ($resp->getScore() < 0.5) { $fail('Verification failed due to low score.'); }
    }
}
