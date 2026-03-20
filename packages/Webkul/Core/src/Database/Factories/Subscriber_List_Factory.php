<?php

declare (strict_types=1);
namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\Subscribers_List;
class Subscriber_List_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subscribers_List::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return ['email' => $this->faker->safe_email(), 'channel_id' => core()->get_current_channel()->id, 'is_subscribed' => 1, 'token' => uniqid()];
    }
}