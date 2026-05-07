<!-- Sagawa Delivery Date/Time Selection Component -->
<!-- Usage in checkout/order page:
    @include('admin::catalog.orders.sagawa-delivery-selection', [
        'orderId' => $order->id,
        'prefecture' => $order->shipping_address->state,
        'currentDate' => $order->delivery_date,
        'currentTime' => $order->delivery_time
    ])
-->

<div class="sagawa-delivery-component">
    <div class="delivery-header">
        <h3>Sagawa 配送日時選択</h3>
        <p class="delivery-description">ご希望の配送日時をお選びください</p>
    </div>

    <div class="delivery-content">
        <!-- Prefecture Info -->
        <div class="delivery-section">
            <div class="info-box">
                <p class="delivery-estimate">
                    <strong>{{ $prefecture }}</strong> への配送予定：
                    <span id="deliveryEstimate" class="estimate-value">読み込み中...</span>
                </p>
            </div>
        </div>

        <!-- Delivery Date Selection -->
        <div class="delivery-section">
            <label class="section-label">
                <span class="label-icon">📅</span>
                配送日を選択
            </label>
            <div class="delivery-dates" id="deliveryDatesContainer">
                <div class="loading">読み込み中...</div>
            </div>
        </div>

        <!-- Time Slot Selection -->
        <div class="delivery-section">
            <label class="section-label">
                <span class="label-icon">🕐</span>
                配送時間帯を選択（オプション）
            </label>
            <div class="time-slots" id="timeSlotsContainer">
                <div class="loading">読み込み中...</div>
            </div>
        </div>

        <!-- Selected Summary -->
        <div class="delivery-summary">
            <div class="summary-row">
                <span class="summary-label">選択予定日:</span>
                <span class="summary-value" id="selectedDate">未選択</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">選択時間帯:</span>
                <span class="summary-value" id="selectedTime">指定なし</span>
            </div>
        </div>

        <!-- Hidden Form Fields -->
        <input type="hidden" id="deliveryDateInput" name="delivery_date" value="{{ $currentDate ?? '' }}">
        <input type="hidden" id="deliveryTimeInput" name="delivery_time" value="{{ $currentTime ?? '' }}">
    </div>
</div>

<style>
    .sagawa-delivery-component {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 24px;
        color: white;
        margin: 20px 0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .delivery-header {
        margin-bottom: 24px;
        text-align: center;
    }

    .delivery-header h3 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 8px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .delivery-description {
        font-size: 14px;
        opacity: 0.95;
        margin: 0;
    }

    .delivery-content {
        background: white;
        border-radius: 10px;
        padding: 24px;
        color: #333;
    }

    .delivery-section {
        margin-bottom: 24px;
    }

    .delivery-section:last-child {
        margin-bottom: 0;
    }

    .info-box {
        background: #f0f4ff;
        border-left: 4px solid #667eea;
        padding: 16px;
        border-radius: 6px;
        margin-bottom: 16px;
    }

    .delivery-estimate {
        margin: 0;
        font-size: 15px;
        font-weight: 500;
        color: #333;
    }

    .estimate-value {
        color: #667eea;
        font-weight: 700;
        font-size: 16px;
    }

    .section-label {
        display: block;
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 12px;
        color: #333;
        cursor: pointer;
    }

    .label-icon {
        margin-right: 8px;
    }

    /* Delivery Dates Grid */
    .delivery-dates {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 10px;
    }

    .date-option {
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        cursor: pointer;
        text-align: center;
        transition: all 0.3s ease;
        background: white;
    }

    .date-option:hover {
        border-color: #667eea;
        background: #f8f9ff;
        transform: translateY(-2px);
    }

    .date-option.selected {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: #667eea;
    }

    .date-display {
        font-size: 14px;
        font-weight: 600;
    }

    .date-day {
        font-size: 12px;
        opacity: 0.8;
        margin-top: 4px;
    }

    /* Time Slots Grid */
    .time-slots {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
    }

    .time-option {
        padding: 14px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        cursor: pointer;
        text-align: center;
        transition: all 0.3s ease;
        background: white;
        font-size: 14px;
        font-weight: 500;
    }

    .time-option:hover {
        border-color: #764ba2;
        background: #f8f9ff;
        transform: translateY(-2px);
    }

    .time-option.selected {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: #764ba2;
    }

    .time-option.no-time {
        grid-column: 1 / -1;
    }

    /* Summary */
    .delivery-summary {
        background: #f8f9ff;
        border: 2px dashed #667eea;
        border-radius: 8px;
        padding: 16px;
        margin-top: 20px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        font-size: 14px;
    }

    .summary-label {
        font-weight: 600;
        color: #666;
    }

    .summary-value {
        color: #667eea;
        font-weight: 700;
    }

    .loading {
        text-align: center;
        padding: 20px;
        color: #999;
        font-size: 14px;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .sagawa-delivery-component {
            padding: 16px;
            margin: 16px -16px;
            border-radius: 8px;
        }

        .delivery-content {
            padding: 16px;
        }

        .delivery-header h3 {
            font-size: 20px;
        }

        .delivery-dates {
            grid-template-columns: repeat(2, 1fr);
        }

        .time-slots {
            grid-template-columns: repeat(2, 1fr);
        }

        .time-option.no-time {
            grid-column: 1 / -1;
        }

        .date-option,
        .time-option {
            padding: 10px;
            font-size: 13px;
        }
    }

    @media (max-width: 480px) {
        .delivery-dates {
            grid-template-columns: 1fr;
        }

        .time-slots {
            grid-template-columns: 1fr;
        }

        .delivery-estimate {
            font-size: 14px;
        }

        .estimate-value {
            display: block;
            margin-top: 4px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const orderId = @json($orderId ?? null);
        const prefecture = @json($prefecture ?? '');

        if (orderId && prefecture) {
            initializeSagawaDelivery(orderId, prefecture);
        }
    });

    function initializeSagawaDelivery(orderId, prefecture) {
        const endpoint = `/admin/api/orders/delivery-options/${orderId}`;

        fetch(endpoint)
            .then(response => response.json())
            .then(data => {
                renderDeliveryDates(data.delivery_dates);
                renderTimeSlots(data.time_slots);
                updateDeliveryEstimate(prefecture);

                // Restore previous selections if they exist
                if (data.current_date) {
                    document.getElementById('deliveryDateInput').value = data.current_date;
                    markDateAsSelected(data.current_date);
                }
                if (data.current_time) {
                    document.getElementById('deliveryTimeInput').value = data.current_time;
                    markTimeAsSelected(data.current_time);
                }
            })
            .catch(error => {
                console.error('Failed to load delivery options:', error);
                document.getElementById('deliveryDatesContainer').innerHTML = 
                    '<div class="loading" style="color: red;">配送日時の読み込みに失敗しました</div>';
            });
    }

    function renderDeliveryDates(dates) {
        const container = document.getElementById('deliveryDatesContainer');
        
        if (!dates || dates.length === 0) {
            container.innerHTML = '<p class="loading">配送日が取得できませんでした</p>';
            return;
        }

        container.innerHTML = dates.map(date => `
            <div class="date-option" data-date="${date.iso}">
                <div class="date-display">${date.formatted}</div>
                <div class="date-day">${date.day_of_week}</div>
            </div>
        `).join('');

        // Add click handlers
        container.querySelectorAll('.date-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.date-option').forEach(el => el.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('deliveryDateInput').value = this.dataset.date;
                updateSelectedDateDisplay();
            });
        });
    }

    function renderTimeSlots(slots) {
        const container = document.getElementById('timeSlotsContainer');
        
        if (!slots || slots.length === 0) {
            container.innerHTML = '<p class="loading">時間帯が取得できませんでした</p>';
            return;
        }

        const noTimeOption = '<div class="time-option no-time" data-time="">時間帯指定なし</div>';
        const slotOptions = slots.map(slot => 
            `<div class="time-option" data-time="${slot}">${slot}</div>`
        ).join('');

        container.innerHTML = noTimeOption + slotOptions;

        // Add click handlers
        container.querySelectorAll('.time-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.time-option').forEach(el => el.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('deliveryTimeInput').value = this.dataset.time;
                updateSelectedTimeDisplay();
            });
        });
    }

    function updateDeliveryEstimate(prefecture) {
        const estimates = {
            // Zone 1 (1 day)
            '東京都': '翌日配送',
            '埼玉県': '翌日配送',
            '神奈川県': '翌日配送',
            '千葉県': '翌日配送',
            
            // Zone 2 (2 days)
            '山梨県': '2日後配送',
            '長野県': '2日後配送',
            '群馬県': '2日後配送',
            '栃木県': '2日後配送',
            '茨城県': '2日後配送',
            '静岡県': '2日後配送',
            
            // Zone 3 (2-3 days)
            '愛知県': '2-3日後配送',
            '岐阜県': '2-3日後配送',
            '三重県': '2-3日後配送',
            '奈良県': '2-3日後配送',
            
            // Zone 4 (3 days)
            '京都府': '3日後配送',
            '大阪府': '3日後配送',
            '兵庫県': '3日後配送',
            '滋賀県': '3日後配送',
            '和歌山県': '3日後配送',
            
            // Zone 5-8 (3-4 days)
            '北海道': '3-4日後配送',
            '青森県': '3-4日後配送',
            '岩手県': '3-4日後配送',
            '宮城県': '3-4日後配送',
            '秋田県': '3-4日後配送',
            '山形県': '3-4日後配送',
            '福島県': '3-4日後配送',
            '新潟県': '3-4日後配送',
            '富山県': '3-4日後配送',
            '石川県': '3-4日後配送',
            '福井県': '3-4日後配送',
            '岡山県': '3-4日後配送',
            '広島県': '3-4日後配送',
            '鳥取県': '3-4日後配送',
            '島根県': '3-4日後配送',
            '山口県': '3-4日後配送',
            '香川県': '3-4日後配送',
            '徳島県': '3-4日後配送',
            '愛媛県': '3-4日後配送',
            '高知県': '3-4日後配送',
            '福岡県': '3-4日後配送',
            '佐賀県': '3-4日後配送',
            '長崎県': '3-4日後配送',
            '熊本県': '3-4日後配送',
            '大分県': '3-4日後配送',
            '宮崎県': '3-4日後配送',
            '鹿児島県': '3-4日後配送',
            '沖縄県': '4-5日後配送',
        };

        const estimate = estimates[prefecture] || '配送日数の確認';
        document.getElementById('deliveryEstimate').textContent = estimate;
    }

    function markDateAsSelected(date) {
        document.querySelectorAll('.date-option').forEach(option => {
            if (option.dataset.date === date) {
                option.classList.add('selected');
            }
        });
        updateSelectedDateDisplay();
    }

    function markTimeAsSelected(time) {
        document.querySelectorAll('.time-option').forEach(option => {
            if (option.dataset.time === time) {
                option.classList.add('selected');
            }
        });
        updateSelectedTimeDisplay();
    }

    function updateSelectedDateDisplay() {
        const selected = document.querySelector('.date-option.selected');
        const display = document.getElementById('selectedDate');
        if (selected) {
            display.textContent = selected.querySelector('.date-display').textContent;
        } else {
            display.textContent = '未選択';
        }
    }

    function updateSelectedTimeDisplay() {
        const selected = document.querySelector('.time-option.selected');
        const display = document.getElementById('selectedTime');
        if (selected) {
            const timeText = selected.dataset.time || '指定なし';
            display.textContent = timeText;
        } else {
            display.textContent = '指定なし';
        }
    }
</script>
