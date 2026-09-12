{{-- Server-side flash messages for the redirect-with-session path used
     throughout this module.

     Class names are written out in full per state rather than interpolated:
     Tailwind scans source text for literal class names, so a constructed
     string like "border-{{ $colour }}-500" is purged from the built CSS and
     the banner renders unstyled. --}}
@if (session('success'))
    <div class="p-3 border rounded text-sm border-green-500 bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-200">
        {{ session('success') }}
    </div>
@endif

@if (session('warning'))
    <div class="p-3 border rounded text-sm border-yellow-500 bg-yellow-50 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200">
        {{ session('warning') }}
    </div>
@endif

@if (session('error'))
    <div class="p-3 border rounded text-sm border-red-500 bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-200">
        {{ session('error') }}
    </div>
@endif

@if (session('info'))
    <div class="p-3 border rounded text-sm border-blue-500 bg-blue-50 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
        {{ session('info') }}
    </div>
@endif

{{-- $errors is injected by the web middleware group (ShareErrorsFromSession).
     Guarded so this partial is also safe to render outside that group. --}}
@if (isset($errors) && $errors->any())
    <div class="p-3 border border-red-500 rounded text-sm bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-200">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
