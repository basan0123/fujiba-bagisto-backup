<?php

// database/factories/UserChannelMapFactory.php

namespace Database\Factories;

use App\Models\UserChannelMap;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserChannelMapFactory extends Factory
{
    protected $model = UserChannelMap::class;

    public function definition(): array
    {
        return [
            'channel_id'          => 1,
            'tenant_id'           => 'tenant-' . fake()->unique()->lexify('????????'),
            'inventory_source_id' => null,
            'is_active'           => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
