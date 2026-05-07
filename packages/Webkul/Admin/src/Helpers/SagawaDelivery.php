<?php

namespace Webkul\Admin\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * Sagawa Delivery Helper
 * 
 * Handles delivery date/time calculation for Sagawa shipping service
 * Based on prefecture zones and business days
 */
class SagawaDelivery
{
    /**
     * Prefecture to Zone mapping
     * Zone determines delivery days from Tokyo (Toda area)
     */
    protected $prefectureZones = [
        // Zone 1: 1-2 days (nearby prefectures)
        '東京都' => 1, '埼玉県' => 1, '神奈川県' => 1, '千葉県' => 1,
        
        // Zone 2: 2 days (nearby regions)
        '山梨県' => 2, '長野県' => 2, '群馬県' => 2, '栃木県' => 2, '茨城県' => 2,
        
        // Zone 3: 2-3 days (Kanto+Chubu)
        '静岡県' => 2, '愛知県' => 3, '岐阜県' => 3, '三重県' => 3, '奈良県' => 3,
        
        // Zone 4: 3 days (Kansai)
        '京都府' => 3, '大阪府' => 3, '兵庫県' => 3, '滋賀県' => 3, '和歌山県' => 3,
        
        // Zone 5: 3-4 days (Western Japan)
        '福岡県' => 4, '佐賀県' => 4, '長崎県' => 4, '熊本県' => 4, '大分県' => 4,
        '宮崎県' => 4, '鹿児島県' => 4, '沖縄県' => 4,
        
        // Zone 6: 3-4 days (Chugoku/Shikoku)
        '広島県' => 4, '岡山県' => 4, '鳥取県' => 4, '島根県' => 4, '山口県' => 4,
        '香川県' => 4, '徳島県' => 4, '高知県' => 4, '愛媛県' => 4,
        
        // Zone 7: 4 days (Tohoku)
        '青森県' => 4, '岩手県' => 4, '宮城県' => 3, '秋田県' => 4, '山形県' => 4, '福島県' => 3,
        
        // Zone 8: 4 days (Hokkaido)
        '北海道' => 4,
    ];

    /**
     * Sagawa delivery time slots
     */
    protected $timeSlots = [
        '午前中 (9:00-12:00)',
        '12:00-14:00',
        '14:00-16:00',
        '16:00-18:00',
        '18:00-20:00',
        '19:00-21:00',
    ];

    /**
     * Japanese holidays (can be expanded)
     */
    protected $holidays = [
        // Format: 'Y-m-d' => 'Holiday Name'
        // Add major holidays here
    ];

    /**
     * Get delivery days for prefecture
     * 
     * @param string $prefecture Prefecture name (Japanese)
     * @return int Delivery days (1-4)
     */
    public function getDeliveryDaysByPrefecture($prefecture)
    {
        return $this->prefectureZones[$prefecture] ?? 4; // Default to 4 days
    }

    /**
     * Get earliest possible delivery date
     * 
     * @param string|Carbon $baseDate Starting date (default: today)
     * @param bool $skipSunday Skip Sundays
     * @return Carbon
     */
    public function getBaseDeliveryDate($baseDate = null, $skipSunday = true)
    {
        $date = $baseDate ? Carbon::parse($baseDate) : Carbon::now();
        
        // Move to next day if after 2 PM (cut-off time)
        if ($date->format('H') >= 14 && $date->isToday()) {
            $date->addDay();
        }

        // Skip weekends and holidays
        while ($this->isHolidayOrSunday($date, $skipSunday)) {
            $date->addDay();
        }

        return $date;
    }

    /**
     * Calculate delivery date from base date + days
     * 
     * @param int $deliveryDays Days to add
     * @param string|Carbon $baseDate Starting date
     * @param bool $skipSunday Skip Sundays
     * @return Carbon
     */
    public function calculateDeliveryDate($deliveryDays, $baseDate = null, $skipSunday = true)
    {
        $base = $this->getBaseDeliveryDate($baseDate, $skipSunday);
        $date = clone $base;
        $daysToAdd = $deliveryDays;

        // Add days, skipping non-business days
        while ($daysToAdd > 0) {
            $date->addDay();
            if (!$this->isHolidayOrSunday($date, $skipSunday)) {
                $daysToAdd--;
            }
        }

        return $date;
    }

    /**
     * Generate selectable delivery dates
     * 
     * @param string $prefecture Prefecture name
     * @param int $count Number of dates to generate (default: 5)
     * @param bool $skipSunday Skip Sundays
     * @return array Array of Carbon dates
     */
    public function getSelectableDeliveryDates($prefecture, $count = 5, $skipSunday = true)
    {
        $deliveryDays = $this->getDeliveryDaysByPrefecture($prefecture);
        $dates = [];

        for ($i = 0; $i < $count; $i++) {
            $date = $this->calculateDeliveryDate($deliveryDays, null, $skipSunday);
            $dates[] = [
                'date' => $date,
                'formatted' => $this->formatDeliveryDate($date),
                'iso' => $date->format('Y-m-d'),
                'day_of_week' => $this->getJapaneseDayOfWeek($date),
            ];
            // Prepare for next iteration
            $deliveryDays = 0; // Next dates just add 1 day
        }

        return $dates;
    }

    /**
     * Format delivery date for display
     * 
     * @param Carbon $date
     * @return string Formatted date (Japanese)
     */
    public function formatDeliveryDate($date)
    {
        $month = $date->month;
        $day = $date->day;
        $dayOfWeek = $this->getJapaneseDayOfWeek($date);
        
        return "{$month}月{$day}日 ({$dayOfWeek})";
    }

    /**
     * Get Japanese day of week
     * 
     * @param Carbon $date
     * @return string
     */
    public function getJapaneseDayOfWeek($date)
    {
        $days = ['日', '月', '火', '水', '木', '金', '土'];
        return $days[$date->dayOfWeek]; // 0 = Sunday
    }

    /**
     * Check if date is holiday or Sunday
     * 
     * @param Carbon $date
     * @param bool $skipSunday
     * @return bool
     */
    public function isHolidayOrSunday($date, $skipSunday = true)
    {
        // Check Sunday
        if ($skipSunday && $date->isSunday()) {
            return true;
        }

        // Check holidays
        if (isset($this->holidays[$date->format('Y-m-d')])) {
            return true;
        }

        return false;
    }

    /**
     * Get time slots
     * 
     * @return array
     */
    public function getTimeSlots()
    {
        return $this->timeSlots;
    }

    /**
     * Get all prefectures (for dropdown)
     * 
     * @return array
     */
    public function getPrefectures()
    {
        return array_keys($this->prefectureZones);
    }

    /**
     * Add Japanese holiday
     * 
     * @param string $date Format: Y-m-d
     * @param string $name Holiday name
     */
    public function addHoliday($date, $name)
    {
        $this->holidays[$date] = $name;
    }

    /**
     * Get delivery estimate message
     * 
     * @param string $prefecture
     * @return string
     */
    public function getDeliveryEstimate($prefecture)
    {
        $days = $this->getDeliveryDaysByPrefecture($prefecture);
        $date = $this->calculateDeliveryDate($days);
        
        return "配送予定日: {$this->formatDeliveryDate($date)}";
    }
}
