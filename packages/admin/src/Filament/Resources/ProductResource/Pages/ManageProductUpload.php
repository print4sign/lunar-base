<?php

namespace Lunar\Admin\Filament\Resources\ProductResource\Pages;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Support\Concerns\Products\ChecksDynamicHiddenSections;
use Lunar\Admin\Support\Pages\BaseEditRecord;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Drivers\Suppliers\AbstractSupplierDriver;
use Lunar\Models\Contracts\ProductVariant as ProductVariantContract;

class ManageProductUpload extends BaseEditRecord
{
    use ChecksDynamicHiddenSections;

    protected static string $resource = ProductResource::class;

    public bool $requiresUpload = false;

    public array $uploaders = [];

    public function getTitle(): string|Htmlable
    {
        return __('lunarpanel::product.pages.upload.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('lunarpanel::product.pages.upload.label');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        // Must be a single-variant product
        if ($parameters['record']->variants()->withTrashed()->count() != 1) {
            return false;
        }

        // Hide if variant is dynamic and driver hides upload section
        if (static::shouldHideSectionForDynamic($parameters, AbstractSupplierDriver::SECTION_UPLOAD)) {
            return false;
        }

        return true;
    }

    public function getBreadcrumb(): string
    {
        return static::getNavigationLabel();
    }

    public static function getNavigationIcon(): ?string
    {
        return FilamentIcon::resolve('lunar::product-upload');
    }

    protected function getDefaultHeaderActions(): array
    {
        return [];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $variant = $this->getVariant();
        $uploadSpec = $variant->upload_spec;

        if ($uploadSpec) {
            $this->requiresUpload = $uploadSpec->upload;
            $this->uploaders = $uploadSpec->uploaders->map(fn ($u) => $u->toArray())->toArray();
        } else {
            $this->requiresUpload = false;
            $this->uploaders = [];
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $variant = $this->getVariant();

        if (! $this->requiresUpload || empty($this->uploaders)) {
            $variant->upload_spec = null;
        } else {
            $variant->upload_spec = UploadSpec::fromArray([
                'upload' => true,
                'uploaders' => $this->uploaders,
                'version' => UploadSpec::VERSION,
                'source' => UploadSpec::SOURCE_STATIC,
            ]);
        }

        $variant->save();

        return $record;
    }

    protected function getVariant(): ProductVariantContract
    {
        return $this->getRecord()->variants()->withTrashed()->first();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('lunarpanel::productvariant.pages.upload.form.section_title'))
                ->description(__('lunarpanel::productvariant.pages.upload.form.section_description'))
                ->schema([
                    Toggle::make('requiresUpload')
                        ->label(__('lunarpanel::productvariant.pages.upload.form.requires_upload.label'))
                        ->helperText(__('lunarpanel::productvariant.pages.upload.form.requires_upload.helper'))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state && empty($this->uploaders)) {
                                $set('uploaders', [
                                    $this->getDefaultUploader(),
                                ]);
                            }
                        }),

                    Repeater::make('uploaders')
                        ->label(__('lunarpanel::productvariant.pages.upload.form.uploaders.label'))
                        ->visible(fn ($get) => $get('requiresUpload'))
                        ->schema([
                            Select::make('type')
                                ->label(__('lunarpanel::productvariant.pages.upload.form.type.label'))
                                ->options([
                                    'single' => __('lunarpanel::productvariant.pages.upload.form.type.options.single'),
                                    'frontback' => __('lunarpanel::productvariant.pages.upload.form.type.options.frontback'),
                                    'multipage' => __('lunarpanel::productvariant.pages.upload.form.type.options.multipage'),
                                    'custom' => __('lunarpanel::productvariant.pages.upload.form.type.options.custom'),
                                ])
                                ->default('single')
                                ->required(),

                            TextInput::make('amount')
                                ->label(__('lunarpanel::productvariant.pages.upload.form.amount.label'))
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->maxValue(100),

                            Fieldset::make(__('lunarpanel::productvariant.pages.upload.form.dimensions.label'))
                                ->schema([
                                    TextInput::make('width')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.width.label'))
                                        ->numeric()
                                        ->suffix('mm')
                                        ->nullable(),

                                    TextInput::make('height')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.height.label'))
                                        ->numeric()
                                        ->suffix('mm')
                                        ->nullable(),

                                    TextInput::make('length')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.length.label'))
                                        ->numeric()
                                        ->suffix('mm')
                                        ->nullable(),
                                ])
                                ->columns(3),

                            Fieldset::make(__('lunarpanel::productvariant.pages.upload.form.quality.label'))
                                ->schema([
                                    TextInput::make('minimal_dpi')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.minimal_dpi.label'))
                                        ->numeric()
                                        ->default(72)
                                        ->minValue(72)
                                        ->maxValue(600),

                                    TextInput::make('file_limit')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.file_limit.label'))
                                        ->numeric()
                                        ->default(500)
                                        ->suffix('MB'),
                                ])
                                ->columns(2),

                            Fieldset::make(__('lunarpanel::productvariant.pages.upload.form.features.label'))
                                ->schema([
                                    Toggle::make('mirror_enabled')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.mirror_enabled.label'))
                                        ->default(false),

                                    Toggle::make('fill_enabled')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.fill_enabled.label'))
                                        ->default(true),

                                    Toggle::make('rotation_enabled')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.rotation_enabled.label'))
                                        ->default(true),

                                    Toggle::make('require_white_spot')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.require_white_spot.label'))
                                        ->default(false),
                                ])
                                ->columns(2),

                            Fieldset::make(__('lunarpanel::productvariant.pages.upload.form.tiling.label'))
                                ->schema([
                                    Toggle::make('tiling_enabled')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.tiling_enabled.label'))
                                        ->default(false)
                                        ->live(),

                                    Toggle::make('tiling_mandatory')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.tiling_mandatory.label'))
                                        ->default(false)
                                        ->visible(fn ($get) => $get('tiling_enabled')),

                                    Select::make('tiling_direction')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.tiling_direction.label'))
                                        ->options([
                                            'horizontal' => __('lunarpanel::productvariant.pages.upload.form.tiling_direction.options.horizontal'),
                                            'vertical' => __('lunarpanel::productvariant.pages.upload.form.tiling_direction.options.vertical'),
                                        ])
                                        ->visible(fn ($get) => $get('tiling_enabled'))
                                        ->nullable(),
                                ])
                                ->columns(3),

                            Fieldset::make(__('lunarpanel::productvariant.pages.upload.form.bleed.label'))
                                ->schema([
                                    TextInput::make('bleed_top')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.bleed_top.label'))
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('mm'),

                                    TextInput::make('bleed_right')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.bleed_right.label'))
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('mm'),

                                    TextInput::make('bleed_bottom')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.bleed_bottom.label'))
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('mm'),

                                    TextInput::make('bleed_left')
                                        ->label(__('lunarpanel::productvariant.pages.upload.form.bleed_left.label'))
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('mm'),
                                ])
                                ->columns(4),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel(__('lunarpanel::productvariant.pages.upload.form.add_uploader'))
                        ->reorderable(true)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => match ($state['type'] ?? 'single') {
                            'single' => __('lunarpanel::productvariant.pages.upload.form.type.options.single'),
                            'frontback' => __('lunarpanel::productvariant.pages.upload.form.type.options.frontback'),
                            'multipage' => __('lunarpanel::productvariant.pages.upload.form.type.options.multipage'),
                            'custom' => __('lunarpanel::productvariant.pages.upload.form.type.options.custom'),
                            default => null,
                        }),
                ]),
        ])->statePath('');
    }

    protected function getDefaultUploader(): array
    {
        return [
            'type' => 'single',
            'amount' => 1,
            'width' => null,
            'height' => null,
            'length' => null,
            'minimal_dpi' => 72,
            'file_limit' => 500,
            'mirror_enabled' => false,
            'fill_enabled' => true,
            'rotation_enabled' => true,
            'require_white_spot' => false,
            'tiling_enabled' => false,
            'tiling_mandatory' => false,
            'tiling_direction' => null,
            'bleed_top' => 0,
            'bleed_right' => 0,
            'bleed_bottom' => 0,
            'bleed_left' => 0,
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
