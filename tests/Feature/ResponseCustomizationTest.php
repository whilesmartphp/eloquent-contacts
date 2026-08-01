<?php

namespace Tests\Feature;

use Illuminate\Http\JsonResponse;
use Tests\TestCase;
use Whilesmart\Contacts\Contracts\ResponseFormatter;
use Whilesmart\Contacts\Http\Resources\ContactResource;

class CustomContactResource extends ContactResource
{
    public function toArray($request): array
    {
        return [...parent::toArray($request), 'custom_resource' => true];
    }
}

class CustomContactResponseFormatter implements ResponseFormatter
{
    public function format(array $payload, int $statusCode = 200): JsonResponse
    {
        return response()->json([...$payload, 'custom_formatter' => true], $statusCode);
    }
}

class ResponseCustomizationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('contacts.resource', CustomContactResource::class);
        $app['config']->set('contacts.response_formatter', CustomContactResponseFormatter::class);
    }

    public function test_it_formats_a_configured_resource_through_the_http_api(): void
    {
        $response = $this->postJson('/api/contacts', [
            'owner_type' => 'workspace',
            'owner_id' => 1,
            'first_name' => 'Jane',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.custom_resource', true)
            ->assertJsonPath('custom_formatter', true);
    }

    public function test_the_formatter_receives_delete_status_and_payload(): void
    {
        $contact = $this->postJson('/api/contacts', [
            'owner_type' => 'workspace',
            'owner_id' => 1,
            'first_name' => 'Jane',
        ])->json('data');

        $this->deleteJson('/api/contacts/'.$contact['id'])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Contact deleted.',
                'custom_formatter' => true,
            ]);
    }
}
