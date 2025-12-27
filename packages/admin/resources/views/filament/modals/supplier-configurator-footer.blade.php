<div
    x-data="{
        costPrice: null,
        sellPrice: null,
        selectionsCount: 0
    }"
    @configurator-pricing-updated.window="
        costPrice = $event.detail.costPrice;
        sellPrice = $event.detail.sellPrice;
        selectionsCount = $event.detail.selectionsCount;
    "
    class="w-full border-b border-gray-200 dark:border-gray-700 pb-3 mb-3"
>
    <div class="flex items-center justify-between gap-4 text-sm">
        <div class="flex items-center gap-6">
            <template x-if="sellPrice">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ __('lunarpanel::product.configurator.cost_price') }}:
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="costPrice"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ __('lunarpanel::product.configurator.sell_price') }}:
                        </span>
                        <span class="text-lg font-bold text-primary-600 dark:text-primary-400" x-text="sellPrice"></span>
                    </div>
                </div>
            </template>
            <template x-if="!sellPrice">
                <span class="text-gray-500 dark:text-gray-400">
                    {{ __('lunarpanel::product.configurator.complete_config_for_price') }}
                </span>
            </template>
        </div>
        <template x-if="selectionsCount > 0">
            <span class="text-gray-500 dark:text-gray-400">
                <span x-text="selectionsCount"></span> {{ __('lunarpanel::product.configurator.selections_made') }}
            </span>
        </template>
    </div>
</div>
