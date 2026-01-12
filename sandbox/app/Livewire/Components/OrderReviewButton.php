<?php

namespace App\Livewire\Components;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Lunar\Models\Order;

class OrderReviewButton extends Component
{
    public Order $order;

    #[Computed]
    public function canReview(): bool
    {
        return $this->order->canBeReviewed();
    }

    #[Computed]
    public function existingInspiration()
    {
        return $this->order->inspirations()->first();
    }

    public function render()
    {
        return view('livewire.components.order-review-button');
    }
}
