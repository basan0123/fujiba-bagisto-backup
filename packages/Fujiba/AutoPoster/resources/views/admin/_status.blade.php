{{-- Status badge. --}}
@php($map = [
    'draft'      => 'ap-tone-gray',
    'scheduled'  => 'ap-tone-blue',
    'processing' => 'ap-tone-amber ap-badge-processing',
    'published'  => 'ap-tone-green',
    'failed'     => 'ap-tone-red',
    'cancelled'  => 'ap-tone-gray',
])

<span class="ap-badge {{ $map[$status] ?? $map['draft'] }}">{{ $status }}</span>
