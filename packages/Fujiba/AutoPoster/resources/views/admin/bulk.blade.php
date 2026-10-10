<x-admin::layouts>
    <x-slot:title>Auto Poster — Bulk Schedule</x-slot:title>

    <div class="ap">
        @include('autoposter::admin._header', [
            'title'    => 'Bulk schedule',
            'subtitle' => 'Turn a batch of products into a week of posts in one go.',
        ])

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        <div class="ap-alert ap-alert-info">
            @include('autoposter::admin._icon', ['i' => 'info'])
            <div class="ap-alert-body">
                Selected products are split into posts of <strong>{{ $settings->products_per_post }}</strong>,
                spread across the daily posting slots from your start date. Every post is created as
                <strong>scheduled</strong> — review each one in the queue before it goes live.
                <span class="ap-badge ap-tone-green" style="margin-left: 6px;">{{ $eligibleCount }} kimono available</span>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.autoposter.bulk.store') }}" class="ap-grid">
            @csrf
            <input type="hidden" name="channel_id" value="{{ $channelId }}">

            <div class="ap-card ap-rise">
                <div class="ap-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); align-items: end;">
                    <label class="ap-field">
                        <span class="ap-label">Start date</span>
                        <input type="date" name="start_date" value="{{ now($settings->timezone ?: 'Asia/Tokyo')->addDay()->toDateString() }}">
                    </label>

                    <label class="ap-field">
                        <span class="ap-label">Spread over (max days)</span>
                        <input type="number" name="max_days" min="1" max="60" value="14">
                    </label>

                    <button class="ap-btn ap-btn-violet ap-btn-lg ap-btn-block"
                            onclick="return confirm('Create scheduled posts from the selected products?');">
                        @include('autoposter::admin._icon', ['i' => 'calendar']) Build schedule
                    </button>
                </div>
            </div>

            <div class="ap-card ap-rise">
                <div class="ap-card-head">
                    <h2 class="ap-card-title">@include('autoposter::admin._icon', ['i' => 'box']) Select products</h2>
                </div>
                @include('autoposter::admin._picker')
            </div>
        </form>
    </div>
</x-admin::layouts>
