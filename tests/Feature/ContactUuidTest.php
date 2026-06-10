<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactUuidTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('contacts.uuids', true);
    }

    #[Test]
    public function it_uses_uuid_primary_keys_when_enabled(): void
    {
        $id = $this->postJson('/api/contacts', [
            'owner_type' => 'App\\Models\\Workspace',
            'owner_id' => '11111111-1111-1111-1111-111111111111',
            'contactable_type' => 'App\\Models\\Customer',
            'contactable_id' => '99999999-9999-9999-9999-999999999999',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ])->assertCreated()->json('data.id');

        $this->assertIsString($id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $id,
        );
    }
}
