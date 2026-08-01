<?php

namespace Whilesmart\Contacts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Whilesmart\Contacts\Contracts\ResponseFormatter;
use Whilesmart\Contacts\Http\Requests\StoreContactRequest;
use Whilesmart\Contacts\Http\Requests\UpdateContactRequest;
use Whilesmart\Contacts\Models\Contact;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerController;

class ContactController extends Controller
{
    use AuthorizesOwnerController;

    public function index(Request $request): JsonResponse
    {
        $model = $this->model();
        $query = $this->scopeAccessibleOwners($model::query(), $request->user());

        if ($request->filled('owner_type') && $request->filled('owner_id')) {
            $query->where('owner_type', $request->input('owner_type'))
                ->where('owner_id', $request->input('owner_id'));
        }

        if ($request->filled('contactable_type') && $request->filled('contactable_id')) {
            $query->where('contactable_type', $request->input('contactable_type'))
                ->where('contactable_id', $request->input('contactable_id'));
        }

        if ($request->filled('q')) {
            $term = '%'.strtolower($request->input('q')).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('lower(first_name) like ?', [$term])
                    ->orWhereRaw('lower(last_name) like ?', [$term])
                    ->orWhereRaw('lower(email) like ?', [$term]);
            });
        }

        $contacts = $query->orderByDesc('is_primary')
            ->orderBy('first_name')
            ->paginate((int) $request->input('per_page', 25));

        return $this->response([
            'success' => true,
            'data' => $this->resource()::collection($contacts)->response()->getData(true),
        ]);
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $model = $this->model();
        $contact = $model::create($request->validated());

        return $this->response([
            'success' => true,
            'data' => new ($this->resource())($contact),
        ], 201);
    }

    public function show(Request $request, $contact): JsonResponse
    {
        $this->authorizeAccessTo($contact, $request->user());

        return $this->response([
            'success' => true,
            'data' => new ($this->resource())($contact),
        ]);
    }

    public function update(UpdateContactRequest $request, $contact): JsonResponse
    {
        $this->authorizeAccessTo($contact, $request->user());
        $contact->update($request->validated());

        return $this->response([
            'success' => true,
            'data' => new ($this->resource())($contact->fresh()),
        ]);
    }

    public function destroy(Request $request, $contact): JsonResponse
    {
        $this->authorizeAccessTo($contact, $request->user());
        $contact->delete();

        return $this->response([
            'success' => true,
            'message' => 'Contact deleted.',
        ]);
    }

    private function model(): string
    {
        return config('contacts.model', Contact::class);
    }

    private function resource(): string
    {
        $resource = config('contacts.resource');

        if (! is_a($resource, JsonResource::class, true)) {
            throw new \InvalidArgumentException('The configured contact resource must extend '.JsonResource::class.'.');
        }

        return $resource;
    }

    private function response(array $payload, int $statusCode = 200): JsonResponse
    {
        return app(ResponseFormatter::class)->format($payload, $statusCode);
    }
}
