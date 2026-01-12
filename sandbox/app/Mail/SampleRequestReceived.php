<?php

namespace App\Mail;

use App\Models\SampleRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SampleRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SampleRequest $sampleRequest,
        public array $sampleLabels
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nieuwe sample aanvraag #' . $this->sampleRequest->id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.sample-request-received',
            with: [
                'sampleRequest' => $this->sampleRequest,
                'sampleLabels' => $this->sampleLabels,
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
}
