<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\Contacts\Models\Contact;

class ContactApiTest extends TestCase
{
    private const OWNER = 'App\\Models\\Workspace';

    private const PARENT = 'App\\Models\\Customer';

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'owner_type' => self::OWNER,
            'owner_id' => 1,
            'contactable_type' => self::PARENT,
            'contactable_id' => 1,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@acme.test',
            'title' => 'Accounts Payable',
            'address' => '12 Market St, Douala',
        ], $overrides);
    }

    #[Test]
    public function it_creates_a_contact(): void
    {
        $this->postJson('/api/contacts', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.first_name', 'Jane')
            ->assertJsonPath('data.full_name', 'Jane Doe')
            ->assertJsonPath('data.address', '12 Market St, Douala')
            ->assertJsonPath('data.contactable_type', self::PARENT);

        $this->assertDatabaseHas('contacts', [
            'owner_id' => 1,
            'contactable_type' => self::PARENT,
            'contactable_id' => 1,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);
    }

    #[Test]
    public function it_filters_contacts_by_contactable(): void
    {
        Contact::create($this->payload(['first_name' => 'Mine', 'contactable_id' => 1]));
        Contact::create($this->payload(['first_name' => 'Theirs', 'contactable_id' => 2]));

        $this->getJson('/api/contacts?contactable_type='.urlencode(self::PARENT).'&contactable_id=1')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.first_name', 'Mine');
    }

    #[Test]
    public function setting_a_new_primary_demotes_the_previous_one(): void
    {
        $a = $this->postJson('/api/contacts', $this->payload(['first_name' => 'A', 'is_primary' => true]))->json('data');
        $b = $this->postJson('/api/contacts', $this->payload(['first_name' => 'B', 'is_primary' => true]))->json('data');

        $this->getJson("/api/contacts/{$a['id']}")->assertOk()->assertJsonPath('data.is_primary', false);
        $this->getJson("/api/contacts/{$b['id']}")->assertOk()->assertJsonPath('data.is_primary', true);
    }

    #[Test]
    public function it_updates_and_deletes_a_contact(): void
    {
        $contact = Contact::create($this->payload());

        $this->putJson("/api/contacts/{$contact->id}", ['title' => 'Finance Lead'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Finance Lead');

        $this->deleteJson("/api/contacts/{$contact->id}")->assertOk();
        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }
}
