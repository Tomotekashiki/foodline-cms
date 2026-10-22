<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function envelope(): Envelope
    {
        $replyTo = [];
        if (! empty($this->booking->email) && filter_var($this->booking->email, FILTER_VALIDATE_EMAIL)) {
            $replyTo[] = new Address($this->booking->email, $this->booking->full_name);
        }

        $dateStr = $this->booking->event_date ? substr($this->booking->event_date, 0, 10) : '';

        return new Envelope(
            subject: "ახალი ჯავშანი #{$this->booking->id} — {$this->booking->full_name} ({$dateStr})",
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-notification',
            with: [
                'booking' => $this->booking,
                'orderDetails' => $this->normalizeOrderDetails($this->booking->order_details),
            ],
        );
    }

    protected function normalizeOrderDetails(mixed $details): array
    {
        if (is_string($details)) {
            $details = json_decode($details, true) ?: [];
        }
        if (! is_array($details)) {
            $details = [];
        }

        $combos = $details['combos'] ?? [];
        if (empty($combos) && ! empty($details['combo'])) {
            $combos = [$details['combo']];
        }

        return [
            'custom_items' => $details['custom_items'] ?? [],
            'combos' => $combos,
        ];
    }
}
