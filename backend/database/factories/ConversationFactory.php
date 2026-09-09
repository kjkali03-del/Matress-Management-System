<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Conversation> */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'channel' => 'whatsapp',
            'status' => 'open',
            'last_message_at' => null,
        ];
    }
}