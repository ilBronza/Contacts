<?php

namespace IlBronza\Contacts\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class ContactEmailVerificationMail extends Mailable
{
    use Queueable;

    public function __construct(public string $verificationUrl, public int $expiresInDays) {}

    public function build(): static
    {
        return $this->subject(__('contacts::verification.subject'))
            ->view('contacts::emails.verify-contact')
            ->text('contacts::emails.verify-contact-text')
            ->with([
                'brandName' => config('contacts.emailVerification.email.brand_name')
                    ?: config('app.name') ?: config('mail.from.name', ''),
                'logoPath' => $this->logoPath(),
            ]);
    }

    private function logoPath(): ?string
    {
        $path = config('contacts.emailVerification.email.logo_path');

        // Embed a local asset; sending a confirmation never needs to fetch a logo.
        return is_string($path) && ! str_contains($path, '://') && is_file($path) && is_readable($path)
            ? $path : null;
    }
}
