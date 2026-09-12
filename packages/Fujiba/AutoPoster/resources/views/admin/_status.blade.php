{{-- Status pill. Literal class names per state — see _flash.blade.php on why
     interpolated Tailwind classes get purged. --}}
@php($map = [
    'draft'      => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
    'scheduled'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200',
    'processing' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
    'published'  => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200',
    'failed'     => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200',
    'cancelled'  => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
])

<span class="px-2 py-0.5 rounded text-xs capitalize {{ $map[$status] ?? $map['draft'] }}">
    {{ $status }}
</span>
