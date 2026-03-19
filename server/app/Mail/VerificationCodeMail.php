<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $code Код подтверждения
     * @param string $type Тип операции (login/registration)
     */
    public function __construct(
        public readonly string $code,
        public readonly string $type,
    ) {}

    /**
     * Заголовок письма.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Код подтверждения — Музей «Иркутское юнкерское училище»',
        );
    }

    /**
     * Содержимое письма.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-code',
        );
    }
}
