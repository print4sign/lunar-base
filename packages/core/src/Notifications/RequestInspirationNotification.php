<?php

namespace Lunar\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Lunar\Models\InspirationRequest;
use Lunar\Models\Order;

class RequestInspirationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public InspirationRequest $request
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $submitUrl = route('inspiration.submit.token', [
            'locale' => app()->getLocale(),
            'token' => $this->request->token,
        ]);

        $productName = $this->order->productLines->first()?->description ?? 'je bestelling';

        return (new MailMessage)
            ->subject(__('lunar::inspiration.email.subject', ['reference' => $this->order->reference]))
            ->greeting(__('lunar::inspiration.email.greeting', ['name' => $notifiable->name ?? 'Klant']))
            ->line(__('lunar::inspiration.email.intro', ['product' => $productName]))
            ->line(__('lunar::inspiration.email.request'))
            ->action(__('lunar::inspiration.email.button'), $submitUrl)
            ->line(__('lunar::inspiration.email.benefits'))
            ->line(__('lunar::inspiration.email.thanks'))
            ->salutation(__('lunar::inspiration.email.salutation'));
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_reference' => $this->order->reference,
            'request_id' => $this->request->id,
        ];
    }
}
