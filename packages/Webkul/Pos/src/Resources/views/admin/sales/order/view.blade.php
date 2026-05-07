@php
    use Webkul\Pos\Repositories\OrderRepository;

    $order = $order ?? null;

    $posOrder = app(OrderRepository::class)->findOneWhere([
        'order_id' => $order?->id,
    ]);
@endphp

@if ($posOrder)
    <!-- POS order note information -->
    <x-admin::accordion>
        <x-slot:header>
            <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                @lang('pos::app.admin.sales.orders.view.order-note')
            </p>
        </x-slot>

        <x-slot:content>
            <div class="flex flex-col gap-1.5">
                <p class="text-gray-600 dark:text-gray-300">
                    @if (Str::length($posOrder->order_note))
                        {{ $posOrder->order_note }}
                    @else
                        @lang('pos::app.admin.sales.orders.view.note-not-available')
                    @endif
                </p>
            </div>
        </x-slot>
    </x-admin::accordion>
@endif
