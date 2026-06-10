<?php

namespace Whilesmart\Contacts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Whilesmart\Contacts\Models\Contact;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'title' => $this->faker->jobTitle(),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
