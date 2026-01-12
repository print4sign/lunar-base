<?php

namespace Lunar\Admin\Livewire\Components;

use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;
use Lunar\Models\ProductVariant;
use Lunar\Services\AIProductMatcherService;

class AIProductMatcher extends Component
{
    public ?int $variantId = null;
    public bool $isOpen = false;
    public bool $isLoading = false;
    public array $matches = [];
    public array $selectedSuppliers = [];
    public ?string $error = null;

    protected AIProductMatcherService $matcherService;

    public function boot(AIProductMatcherService $matcherService): void
    {
        $this->matcherService = $matcherService;
    }

    #[On('open-ai-matcher-modal')]
    public function openModal(int $variantId): void
    {
        $this->variantId = $variantId;
        $this->isOpen = true;
        $this->matches = [];
        $this->error = null;
        $this->selectedSuppliers = [];

        // Auto-run matching on open
        $this->findMatches();
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->matches = [];
        $this->error = null;
        $this->variantId = null;
        $this->selectedSuppliers = [];
    }

    public function findMatches(): void
    {
        if (!$this->variantId) {
            return;
        }

        $this->isLoading = true;
        $this->error = null;
        $this->matches = [];

        try {
            $variant = ProductVariant::findOrFail($this->variantId);

            $matchResults = $this->matcherService->findMatches(
                variant: $variant,
                limit: 10,
                supplierIds: $this->selectedSuppliers
            );

            $this->matches = $matchResults->map->toArray()->toArray();

            if (empty($this->matches)) {
                $this->error = __('lunarpanel::productvariant.fulfillment.ai_match.no_matches');
            }
        } catch (\Exception $e) {
            \Log::error('AI Product Matcher Error', [
                'variant_id' => $this->variantId,
                'error' => $e->getMessage(),
            ]);

            $this->error = __('lunarpanel::productvariant.fulfillment.ai_match.error', [
                'message' => $e->getMessage(),
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectMatch(int $supplierProductId): void
    {
        if (!$this->variantId) {
            return;
        }

        try {
            $variant = ProductVariant::findOrFail($this->variantId);

            // Update variant with selected supplier product
            $variant->update([
                'supplier_product_id' => $supplierProductId,
            ]);

            Notification::make()
                ->title(__('lunarpanel::productvariant.fulfillment.ai_match.linked_success'))
                ->success()
                ->send();

            $this->closeModal();

            // Redirect to refresh the page and show the new supplier link
            $this->redirect(request()->header('Referer'));
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('lunarpanel::productvariant.fulfillment.ai_match.link_error'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getAvailableSuppliers()
    {
        return \Lunar\Models\Supplier::enabled()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function render()
    {
        return view('lunarpanel::livewire.components.ai-product-matcher', [
            'availableSuppliers' => $this->getAvailableSuppliers(),
        ]);
    }
}
