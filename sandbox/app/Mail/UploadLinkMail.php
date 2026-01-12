<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Lunar\Models\OrderLine;
use Lunar\Services\DeliverLaterService;

class UploadLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $uploadUrl;

    public string $expiresAt;

    public ?string $orderReference;

    public string $productName;

    public function __construct(
        public OrderLine $orderLine,
        string $uploadUrl
    ) {
        $this->uploadUrl = $uploadUrl;

        $deliverLaterService = app(DeliverLaterService::class);
        $validityDays = $deliverLaterService->getTokenValidityDays();
        $this->expiresAt = now()->addDays($validityDays)->format('d-m-Y');

        $this->orderReference = $this->orderLine->order?->reference;

        $this->productName = $this->orderLine->purchasable?->getDescription()
            ?? $this->orderLine->description
            ?? __('Your product');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.upload_link.subject', [
                'reference' => $this->orderReference,
            ]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.upload-link',
            with: [
                'orderLine' => $this->orderLine,
                'uploadUrl' => $this->uploadUrl,
                'expiresAt' => $this->expiresAt,
                'orderReference' => $this->orderReference,
                'productName' => $this->productName,
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
