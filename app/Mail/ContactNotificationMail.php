<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage)
    {
    }

    public function envelope(): Envelope
    {
        $replyTo = [];
        if (! empty($this->contactMessage->email) && filter_var($this->contactMessage->email, FILTER_VALIDATE_EMAIL)) {
            $replyTo[] = new Address($this->contactMessage->email, $this->contactMessage->name);
        }

        $subject = ! empty($this->contactMessage->subject)
            ? "ახალი შეტყობინება საიტიდან: {$this->contactMessage->subject} ({$this->contactMessage->name})"
            : "ახალი შეტყობინება საიტიდან: {$this->contactMessage->name}";

        return new Envelope(
            subject: $subject,
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-notification',
            with: [
                'msg' => $this->contactMessage,
            ],
        );
    }
}
