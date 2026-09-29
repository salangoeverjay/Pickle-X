<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Every PickleX email: a Blade template under resources/views/emails, its subject,
 * the inbox preview line and the data the template needs. Built by App\Support\Emails.
 */
class PickleXMail extends Mailable
{
    public function __construct(
        public string $template,
        public string $heading,
        public string $preheader,
        public array $data = [],
        public ?string $fromName = null,
        public bool $security = false,
    ) {}

    public function envelope(): Envelope
    {
        $from = $this->security
            ? new Address(config('mail.security_address'), 'PickleX')
            : new Address(config('mail.from.address'), $this->fromName ?? config('mail.from.name'));

        return new Envelope(from: $from, subject: $this->heading);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.'.$this->template, with: $this->data + ['preheader' => $this->preheader]);
    }
}
