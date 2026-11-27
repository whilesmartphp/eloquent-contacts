<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\Contacts\ContactsServiceProvider;
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
    public function contact_uuids_are_unique_and_survive_a_rename(): void
    {
        $first = $this->postJson('/api/contacts', $this->payload())->assertCreated()->json('data');
        $second = $this->postJson('/api/contacts', $this->payload())->assertCreated()->json('data');

        $this->assertTrue(Str::isUuid($first['uuid']));
        $this->assertNotSame($first['uuid'], $second['uuid']);
        $this->putJson("/api/contacts/{$first['id']}", ['first_name' => 'Jo'])
            ->assertOk()->assertJsonPath('data.uuid', $first['uuid']);
    }

    #[Test]
    public function the_database_rejects_contacts_without_a_uuid(): void
    {
        $this->expectException(QueryException::class);
        DB::table('contacts')->insert($this->payload());
    }

    #[Test]
    public function a_uuid_supplied_by_the_application_is_kept(): void
    {
        $uuid = (string) Str::uuid();
        $contact = new Contact($this->payload());
        $contact->uuid = $uuid;
        $contact->save();

        $this->getJson("/api/contacts/{$contact->id}")->assertOk()->assertJsonPath('data.uuid', $uuid);
    }

    #[Test]
    public function a_supplied_uuid_must_be_valid(): void
    {
        $contact = new Contact($this->payload());
        $contact->uuid = 'not-a-uuid';

        $this->expectException(InvalidArgumentException::class);
        $contact->save();
    }

    #[Test]
    public function the_upgrade_runs_only_after_it_is_published(): void
    {
        $this->assertDatabaseMissing('migrations', ['migration' => '2026_11_18_000001_add_uuids_to_existing_contacts']);
        $this->assertSame(
            [realpath(__DIR__.'/../../database/upgrades/2026_11_18_000001_add_uuids_to_existing_contacts.php')],
            array_map('realpath', array_keys(ServiceProvider::pathsToPublish(ContactsServiceProvider::class, 'contacts-upgrade-2'))),
        );
    }

    #[Test]
    public function upgrading_existing_contacts_assigns_stable_uuids(): void
    {
        $id = $this->postJson('/api/contacts', $this->payload())->assertCreated()->json('data.id');
        Schema::table('contacts', fn (Blueprint $table) => $table->dropUnique(['uuid']));
        Schema::table('contacts', fn (Blueprint $table) => $table->dropColumn('uuid'));

        $upgrade = require __DIR__.'/../../database/upgrades/2026_11_18_000001_add_uuids_to_existing_contacts.php';
        $upgrade->up();
        $uuid = $this->getJson("/api/contacts/{$id}")->assertOk()->json('data.uuid');
        $this->assertTrue(Str::isUuid($uuid));

        $this->assertFalse(collect(Schema::getColumns('contacts'))->firstWhere('name', 'uuid')['nullable']);

        $upgrade->up();
        $this->getJson("/api/contacts/{$id}")->assertOk()->assertJsonPath('data.uuid', $uuid);

        $upgrade->down();
        $this->assertFalse(Schema::hasColumn('contacts', 'uuid'));
        $this->assertDatabaseHas('contacts', ['id' => $id]);
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
