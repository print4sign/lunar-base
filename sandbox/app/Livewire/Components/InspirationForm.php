<?php

namespace App\Livewire\Components;

use App\Livewire\Concerns\HasLocale;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Lunar\Models\Inspiration;
use Lunar\Models\InspirationRequest;
use Lunar\Models\Order;
use Lunar\Services\InspirationImageValidator;

class InspirationForm extends Component
{
    use HasLocale;
    use WithFileUploads;

    public ?Order $order = null;
    public ?string $token = null;
    public bool $submitted = false;

    #[Validate('required|in:review,case_study')]
    public string $type = 'review';

    #[Validate('required|integer|min:1|max:5')]
    public int $rating = 5;

    #[Validate('required|string|min:10|max:2000')]
    public string $text = '';

    #[Validate('nullable|string|max:255')]
    public ?string $title = null;

    #[Validate('nullable|string|max:255')]
    public ?string $companyName = null;

    #[Validate('nullable|string|max:255')]
    public ?string $projectType = null;

    #[Validate('required|accepted')]
    public bool $permissionGranted = false;

    #[Validate(['photos' => 'required|array|min:1', 'photos.*' => 'image|max:10240'])]
    public array $photos = [];

    public function mount(string $locale = 'nl', ?Order $order = null, ?string $token = null): void
    {
        $this->initializeLocale($locale);

        if ($token) {
            $this->token = $token;
            $request = InspirationRequest::findByToken($token);

            if ($request && $request->isValid()) {
                $this->order = $request->order;
                $request->markAsOpened();
            }
        } elseif ($order) {
            // Verify user owns this order
            if (Auth::check() && $order->user_id === Auth::id()) {
                $this->order = $order;
            }
        }
    }

    public function updatedPhotos(): void
    {
        $validator = app(InspirationImageValidator::class);

        foreach ($this->photos as $index => $photo) {
            try {
                $validator->validate($photo);
            } catch (\Exception $e) {
                unset($this->photos[$index]);
                $this->addError("photos.{$index}", $e->getMessage());
            }
        }

        $this->photos = array_values($this->photos);
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function submit(): void
    {
        if (! $this->order) {
            return;
        }

        $this->validate();

        $inspiration = Inspiration::create([
            'order_id' => $this->order->id,
            'order_line_id' => $this->order->productLines()->first()?->id,
            'user_id' => Auth::id(),
            'type' => $this->type,
            'rating' => $this->rating,
            'text' => [app()->getLocale() => $this->text],
            'title' => $this->type === 'case_study' ? [app()->getLocale() => $this->title] : null,
            'company_name' => $this->type === 'case_study' ? $this->companyName : null,
            'project_type' => $this->type === 'case_study' ? $this->projectType : null,
            'permission_granted' => $this->permissionGranted,
            'status' => 'pending',
        ]);

        foreach ($this->photos as $photo) {
            $inspiration->addMedia($photo->getRealPath())
                ->usingFileName($photo->getClientOriginalName())
                ->toMediaCollection('inspiration_photos');
        }

        // Mark request as completed if from email
        if ($this->token) {
            $request = InspirationRequest::findByToken($this->token);
            $request?->markAsCompleted();
        }

        $this->submitted = true;

        $this->dispatch('inspiration-submitted');
    }

    public function render()
    {
        return view('livewire.components.inspiration-form');
    }
}
