@if (auth('admin')->check() && bouncer()->hasPermission('catalog.products.edit'))
    <a href="{{ route('admin.barcode_assign.index') }}"
       style="display:inline-block;padding:8px 14px;background:#2563eb;color:#fff;border-radius:6px;font-weight:600;text-decoration:none;font-size:14px;margin-right:8px;">
        @lang('inventory-control::app.barcode_assign.entry_button')
    </a>
@endif
