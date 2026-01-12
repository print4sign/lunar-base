<?php

namespace App\Livewire\Components;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Lunar\Base\DataTransferObjects\Upload\UploaderRequirement;
use Lunar\Models\CartLine;
use Lunar\Models\PrintAsset;
use Lunar\Services\PrintAssetService;
use Lunar\Services\UploadSpecValidator;

class CustomFileUploader extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $cartLineId;

    #[Locked]
    public int $uploaderIndex;

    #[Locked]
    public array $uploaderRequirement = [];

    /**
     * Temporary files being uploaded.
     *
     * @var array<int, TemporaryUploadedFile|null>
     */
    public array $files = [];

    /**
     * Already uploaded PrintAsset data.
     *
     * @var array<int, array>
     */
    public array $uploadedAssets = [];

    /**
     * Validation errors per file index.
     *
     * @var array<int, array>
     */
    public array $validationErrors = [];

    /**
     * Validation warnings per file index.
     *
     * @var array<int, array>
     */
    public array $validationWarnings = [];

    /**
     * Upload progress per file index.
     *
     * @var array<int, int>
     */
    public array $uploadProgress = [];

    public bool $isUploading = false;

    public function mount(int $cartLineId, int $uploaderIndex, array $uploaderRequirement): void
    {
        $this->cartLineId = $cartLineId;
        $this->uploaderIndex = $uploaderIndex;
        $this->uploaderRequirement = $uploaderRequirement;

        // Load existing assets
        $this->loadExistingAssets();

        // Initialize file slots
        $this->initializeFileSlots();
    }

    /**
     * Load existing print assets for this uploader.
     */
    protected function loadExistingAssets(): void
    {
        $cartLine = CartLine::find($this->cartLineId);

        if (! $cartLine) {
            return;
        }

        $assets = $cartLine->printAssets()
            ->where('uploader_index', $this->uploaderIndex)
            ->orderBy('file_index')
            ->get();

        $this->uploadedAssets = $assets->mapWithKeys(function (PrintAsset $asset) {
            return [
                $asset->file_index => [
                    'id' => $asset->id,
                    'original_filename' => $asset->original_filename,
                    'mime_type' => $asset->mime_type,
                    'size' => $asset->size,
                    'human_size' => $asset->getHumanReadableSize(),
                    'width' => $asset->width,
                    'height' => $asset->height,
                    'dpi' => $asset->dpi,
                    'url' => $asset->isImage() ? $asset->getUrl() : null,
                    'is_image' => $asset->isImage(),
                    'is_pdf' => $asset->isPdf(),
                ],
            ];
        })->toArray();
    }

    /**
     * Initialize file slots based on the uploader requirement.
     */
    protected function initializeFileSlots(): void
    {
        $requirement = UploaderRequirement::fromArray($this->uploaderRequirement);
        $requiredCount = $requirement->getRequiredFileCount();

        for ($i = 0; $i < $requiredCount; $i++) {
            if (! isset($this->files[$i])) {
                $this->files[$i] = null;
            }
            if (! isset($this->uploadProgress[$i])) {
                $this->uploadProgress[$i] = 0;
            }
        }
    }

    /**
     * Get the uploader requirement as an object.
     */
    protected function getUploaderRequirement(): UploaderRequirement
    {
        return UploaderRequirement::fromArray($this->uploaderRequirement);
    }

    /**
     * Handle file selection for a specific slot.
     */
    public function updatedFiles($value, $key): void
    {
        // Key is the file index
        $fileIndex = (int) $key;

        if ($value instanceof TemporaryUploadedFile) {
            $this->validateAndUploadFile($fileIndex);
        }
    }

    /**
     * Validate and upload a file for a specific index.
     */
    public function validateAndUploadFile(int $fileIndex): void
    {
        $file = $this->files[$fileIndex] ?? null;

        if (! $file instanceof TemporaryUploadedFile) {
            return;
        }

        $this->isUploading = true;
        $this->validationErrors[$fileIndex] = [];
        $this->validationWarnings[$fileIndex] = [];
        $this->uploadProgress[$fileIndex] = 10;

        try {
            // Get the cart line
            $cartLine = CartLine::find($this->cartLineId);

            if (! $cartLine) {
                $this->validationErrors[$fileIndex][] = __('Cart line not found.');
                $this->isUploading = false;

                return;
            }

            // Validate the file
            $validator = app(UploadSpecValidator::class);
            $requirement = $this->getUploaderRequirement();

            // Create a temporary array for validation
            $filesToValidate = [$fileIndex => $file];
            $uploaderFiles = [$this->uploaderIndex => $filesToValidate];

            // Create a minimal spec for single-file validation
            $tempSpec = new \Lunar\Base\DataTransferObjects\Upload\UploadSpec(
                upload: true,
                uploaders: collect([$this->uploaderIndex => $requirement]),
            );

            $this->uploadProgress[$fileIndex] = 30;

            // Validate
            $result = $validator->validate($tempSpec, $uploaderFiles);

            $uploaderResult = $result->uploaderResults[$this->uploaderIndex] ?? null;

            if ($uploaderResult) {
                $this->validationErrors[$fileIndex] = $uploaderResult['errors'] ?? [];
                $this->validationWarnings[$fileIndex] = $uploaderResult['warnings'] ?? [];
            }

            // If there are errors, don't upload
            if (! empty($this->validationErrors[$fileIndex])) {
                $this->isUploading = false;

                return;
            }

            $this->uploadProgress[$fileIndex] = 50;

            // Remove existing asset for this slot if any
            if (isset($this->uploadedAssets[$fileIndex])) {
                $existingAsset = PrintAsset::find($this->uploadedAssets[$fileIndex]['id']);
                $existingAsset?->delete();
            }

            $this->uploadProgress[$fileIndex] = 70;

            // Upload the file
            $printAssetService = app(PrintAssetService::class);
            $printAsset = $printAssetService->uploadForCartLine(
                $cartLine,
                $file,
                $this->uploaderIndex,
                $fileIndex
            );

            $this->uploadProgress[$fileIndex] = 100;

            // Update the uploaded assets array
            $this->uploadedAssets[$fileIndex] = [
                'id' => $printAsset->id,
                'original_filename' => $printAsset->original_filename,
                'mime_type' => $printAsset->mime_type,
                'size' => $printAsset->size,
                'human_size' => $printAsset->getHumanReadableSize(),
                'width' => $printAsset->width,
                'height' => $printAsset->height,
                'dpi' => $printAsset->dpi,
                'url' => $printAsset->isImage() ? $printAsset->getUrl() : null,
                'is_image' => $printAsset->isImage(),
                'is_pdf' => $printAsset->isPdf(),
            ];

            // Clear the temporary file
            $this->files[$fileIndex] = null;

            // Dispatch event for parent component
            $this->dispatch('file-uploaded', [
                'cartLineId' => $this->cartLineId,
                'uploaderIndex' => $this->uploaderIndex,
                'fileIndex' => $fileIndex,
                'assetId' => $printAsset->id,
            ]);

            Log::info('CustomFileUploader: File uploaded', [
                'cart_line_id' => $this->cartLineId,
                'uploader_index' => $this->uploaderIndex,
                'file_index' => $fileIndex,
                'asset_id' => $printAsset->id,
            ]);
        } catch (\Exception $e) {
            Log::error('CustomFileUploader: Upload failed', [
                'cart_line_id' => $this->cartLineId,
                'uploader_index' => $this->uploaderIndex,
                'file_index' => $fileIndex,
                'error' => $e->getMessage(),
            ]);

            $this->validationErrors[$fileIndex][] = __('Upload failed: :error', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isUploading = false;
        }
    }

    /**
     * Remove an uploaded file.
     */
    public function removeFile(int $fileIndex): void
    {
        if (! isset($this->uploadedAssets[$fileIndex])) {
            return;
        }

        $assetId = $this->uploadedAssets[$fileIndex]['id'];

        $asset = PrintAsset::find($assetId);
        $asset?->delete();

        unset($this->uploadedAssets[$fileIndex]);

        // Reset progress
        $this->uploadProgress[$fileIndex] = 0;
        $this->validationErrors[$fileIndex] = [];
        $this->validationWarnings[$fileIndex] = [];

        // Dispatch event
        $this->dispatch('file-removed', [
            'cartLineId' => $this->cartLineId,
            'uploaderIndex' => $this->uploaderIndex,
            'fileIndex' => $fileIndex,
        ]);

        Log::info('CustomFileUploader: File removed', [
            'cart_line_id' => $this->cartLineId,
            'uploader_index' => $this->uploaderIndex,
            'file_index' => $fileIndex,
            'asset_id' => $assetId,
        ]);
    }

    /**
     * Get the label for a file slot.
     */
    public function getSlotLabel(int $fileIndex): string
    {
        $requirement = $this->getUploaderRequirement();

        if ($requirement->type === UploaderRequirement::TYPE_FRONTBACK) {
            return $fileIndex === 0 ? __('Front') : __('Back');
        }

        if ($requirement->type === UploaderRequirement::TYPE_MULTIPAGE) {
            return __('Page :number', ['number' => $fileIndex + 1]);
        }

        if ($requirement->amount > 1) {
            return __('File :number', ['number' => $fileIndex + 1]);
        }

        return __('File');
    }

    /**
     * Check if all required files are uploaded.
     */
    public function isComplete(): bool
    {
        $requirement = $this->getUploaderRequirement();
        $requiredCount = $requirement->getRequiredFileCount();

        return count($this->uploadedAssets) >= $requiredCount;
    }

    /**
     * Get the upload completeness status.
     */
    public function getStatus(): array
    {
        $requirement = $this->getUploaderRequirement();
        $requiredCount = $requirement->getRequiredFileCount();
        $uploadedCount = count($this->uploadedAssets);

        return [
            'complete' => $uploadedCount >= $requiredCount,
            'uploaded' => $uploadedCount,
            'required' => $requiredCount,
            'percentage' => $requiredCount > 0 ? round(($uploadedCount / $requiredCount) * 100) : 100,
        ];
    }

    /**
     * Refresh component when parent requests.
     */
    #[On('refresh-uploader')]
    public function refreshUploader(): void
    {
        $this->loadExistingAssets();
    }

    public function render()
    {
        return view('livewire.components.custom-file-uploader', [
            'requirement' => $this->getUploaderRequirement(),
            'status' => $this->getStatus(),
        ]);
    }
}
