@extends('admin::layouts.master')

@section('page_title')
    QR Login URL Generator
@endsection

@section('content')
    <div class="flex flex-col gap-4 p-6">
        <h1 class="text-xl font-semibold">QR Login URL Generator</h1>

        <p class="text-sm text-gray-600 dark:text-gray-400 max-w-2xl">
            Issues a one-time login URL for the given customer. The URL is valid for the
            configured TTL (default 10 minutes), is single-use, and lands the customer on
            their account profile (or a custom target). Paste the URL into any external
            QR generator if a printable code is needed — no QR image library is bundled.
        </p>

        <form method="GET" action="{{ route('admin.fujiba.autologin.qr_generator') }}"
              class="flex flex-col gap-3 max-w-md p-4 border rounded">
            <label class="flex flex-col gap-1 text-sm">
                <span>Customer ID</span>
                <input type="number" name="customer_id" required
                       value="{{ $request->query('customer_id') }}"
                       class="border rounded p-2 dark:bg-gray-800">
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span>TTL (minutes, default 10)</span>
                <input type="number" name="ttl_minutes" min="1" max="120"
                       value="{{ $request->query('ttl_minutes', 10) }}"
                       class="border rounded p-2 dark:bg-gray-800">
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span>Target (optional: orders / profile / wishlist / cart)</span>
                <input type="text" name="target"
                       value="{{ $request->query('target') }}"
                       class="border rounded p-2 dark:bg-gray-800">
            </label>

            <button type="submit"
                    class="bg-blue-600 text-white rounded px-3 py-2 text-sm">
                Generate URL
            </button>
        </form>

        @if ($customer && $url)
            <div class="flex flex-col gap-2 p-4 border rounded max-w-2xl">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Customer: <strong>{{ $customer->first_name }} {{ $customer->last_name }}</strong>
                    ({{ $customer->email }})
                </div>

                <div class="text-sm text-gray-600 dark:text-gray-400">
                    URL (copy / paste into a QR generator):
                </div>

                <input type="text" readonly value="{{ $url }}"
                       onclick="this.select()"
                       class="border rounded p-2 font-mono text-xs dark:bg-gray-800">

                <p class="text-xs text-gray-500">
                    This URL is single-use. Generating a new one for the same customer
                    invalidates nothing — both will work until used or expired.
                </p>
            </div>
        @elseif ($request->query('customer_id') && ! $customer)
            <div class="text-sm text-red-600">
                Customer #{{ (int) $request->query('customer_id') }} not found.
            </div>
        @endif
    </div>
@endsection
