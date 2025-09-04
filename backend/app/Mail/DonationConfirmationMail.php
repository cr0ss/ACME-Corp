<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Donation $donation
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for your donation to ' . $this->donation->campaign->title,
            from: config('mail.from.address'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.donations.confirmation',
            with: [
                'donation' => $this->donation,
                'campaign' => $this->donation->campaign,
                'user' => $this->donation->user,
                'receipt' => $this->getReceiptData(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get receipt data for the email.
     */
    private function getReceiptData(): array
    {
        return [
            'receipt_id' => 'RCP_' . $this->donation->id . '_' . $this->donation->created_at->format('Ymd'),
            'amount' => $this->donation->amount,
            'currency' => 'USD',
            'date' => $this->donation->created_at->toDateString(),
            'payment_method' => $this->donation->payment_method,
            'transaction_id' => $this->donation->transaction_id,
            'message' => $this->donation->message,
            'anonymous' => $this->donation->anonymous,
            'tax_year' => $this->donation->created_at->year,
        ];
    }
}
