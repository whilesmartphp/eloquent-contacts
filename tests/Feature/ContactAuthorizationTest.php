<?php

namespace Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\Contacts\Models\Contact;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class ContactAuthorizationTest extends TestCase
{
    private const OWNER = 'App\\Models\\Workspace';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(OwnerAuthorizer::class, new class implements OwnerAuthorizer
        {
            public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool
            {
                return false;
            }

            public function scope(Builder $query, ?Authenticatable $user, string $ownerTypeColumn = 'owner_type', string $ownerIdColumn = 'owner_id'): Builder
            {
                return $query->whereRaw('0 = 1');
            }
        });
    }

    #[Test]
    public function store_is_forbidden_when_authorizer_denies(): void
    {
        $this->postJson('/api/contacts', [
            'owner_type' => self::OWNER,
            'owner_id' => 1,
            'first_name' => 'Hijacked',
        ])->assertForbidden();

        $this->assertDatabaseCount('contacts', 0);
    }

    #[Test]
    public function show_update_destroy_are_forbidden_when_authorizer_denies(): void
    {
        $contact = Contact::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'first_name' => 'Private']);

        $this->getJson("/api/contacts/{$contact->id}")->assertForbidden();
        $this->putJson("/api/contacts/{$contact->id}", ['first_name' => 'Hijacked'])->assertForbidden();
        $this->deleteJson("/api/contacts/{$contact->id}")->assertForbidden();

        $this->assertSame('Private', $contact->fresh()->first_name);
    }

    #[Test]
    public function index_returns_nothing_when_scope_denies(): void
    {
        Contact::create(['owner_type' => self::OWNER, 'owner_id' => 1, 'first_name' => 'Private']);

        $this->getJson('/api/contacts')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 0);
    }
}
