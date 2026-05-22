@if (auth('admin')->check() && bouncer()->hasPermission('catalog.products.edit'))
    <a href="{{ route('admin.barcode_assign.index') }}"
       title="@lang('inventory-control::app.barcode_assign.entry_button')"
       style="display:inline-flex;align-items:center;justify-content:center;padding:8px 10px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none;line-height:1;">
        <span class="icon-product-1" style="font-size:20px;"></span>
    </a>
@endif
