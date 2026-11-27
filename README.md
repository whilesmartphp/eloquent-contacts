# whilesmart/eloquent-contacts

Polymorphic contacts for Laravel: people (with name, email, phone, title) attached to any
model, scoped to an owner through
[`whilesmart/eloquent-owner-access`](https://github.com/whilesmartphp/eloquent-owner-access).
An org customer with several people, a vendor with an AP desk, anything with humans behind it.

## Install

```
composer require whilesmart/eloquent-contacts
php artisan migrate
```

Routes register automatically under the `api` prefix with `auth:sanctum`. Set
`CONTACTS_REGISTER_ROUTES=false` to mount them yourself.

Applications can keep package-owned routes while replacing only the behavior they need:

```php
'controller' => \App\Http\Controllers\ContactController::class,
'resource' => \App\Http\Resources\ContactResource::class,
'response_formatter' => \App\Http\Responses\ContactResponseFormatter::class,
'route_actions' => ['index', 'store', 'show'],
'route_write_middleware' => ['workspace.write'],
```

The custom resource extends the package resource. The response formatter implements the package
contract and receives the complete response payload and status code. The custom controller can
extend the package controller as a final escape hatch. Write middleware applies to create, update,
and delete actions that remain enabled.

## Attaching contacts to a model

```php
use Whilesmart\Contacts\Traits\HasContacts;

class Customer extends Model
{
    use HasContacts;
}

$customer->contacts()->create([
    'owner_type' => Workspace::class,
    'owner_id' => $workspaceId,
    'first_name' => 'Jane',
    'last_name' => 'Doe',
    'email' => 'ap@acme.test',
    'title' => 'Accounts Payable',
    'address' => '12 Market St, Douala',
    'is_primary' => true,
]);

$customer->primaryContact?->email;       // recipient for billing
$customer->primaryContact?->full_name;   // "Jane Doe"
```

`contacts()` returns every contact; `primaryContact()` returns the one flagged primary. Only
one contact per parent can be primary: setting `is_primary` on one demotes the others
automatically.

## Custom model

The Contact model is swappable. Publish the config and point `contacts.model` at your own
class (extend the package model to add fields/casts/behaviour):

```php
// config/contacts.php
'model' => \App\Models\Contact::class,
```

The trait relations, route binding, and controller all resolve the class from config, so your
model is used everywhere.

## Contact UUIDs

Every contact has a unique `uuid`, separate from its primary key, for use in links and external references. Eloquent assigns one on creation. An application that already has an identifier (for example during an import) can set `$contact->uuid` before saving; it must be a valid UUID. The API never accepts a `uuid` from the request.

## Upgrading from 1.x

2.0 makes `uuid` required. Fresh installs get it from the create migration. Existing installs add it with an upgrade migration that is not loaded automatically, so updating the package never changes the table on its own:

1. Require `whilesmart/eloquent-contacts:^2.0`.
2. Publish the upgrade migration: `php artisan vendor:publish --tag=contacts-upgrade-2`.
3. Pause contact writes, run `php artisan migrate`, then start the updated application and workers.

The migration adds the column, gives every existing contact a UUID, then makes the column required. Running it again does nothing.

## UUID keys

Off by default (auto-incrementing integer ids). To use UUID primary keys, set `CONTACTS_UUIDS=true`
(or `contacts.uuids` config) **before running the migration**. The migration then creates a
`uuid` primary key and `uuid` morph columns, and the model generates an ordered UUID on create.
This assumes the owner and contactable models also use UUID keys.

## Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/api/contacts` | List (filter by `owner_*`, `contactable_type` + `contactable_id`, `q`) |
| POST | `/api/contacts` | Create |
| GET | `/api/contacts/{contact}` | Show |
| PUT/PATCH | `/api/contacts/{contact}` | Update |
| DELETE | `/api/contacts/{contact}` | Soft delete |

List a single parent's people with
`/api/contacts?contactable_type=App\Models\Customer&contactable_id=42`.
