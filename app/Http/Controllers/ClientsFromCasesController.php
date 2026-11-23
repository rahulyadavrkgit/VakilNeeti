<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Turns the people in your cases into clients in one go: the party on your side (from the case's parties,
 * or from its title "A vs B" and the side you appear for), each linked to its case so updates can be sent.
 */
class ClientsFromCasesController extends Controller
{
    public function index(): View
    {
        return view('clients.from-cases', ['suggestions' => $this->suggestions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pick' => ['required', 'array', 'max:500'],
            'pick.*' => ['string', 'max:400'],
        ], ['pick.required' => 'Tick at least one name.']);

        $existing = Client::query()->get()->keyBy(fn (Client $client) => mb_strtolower(trim($client->display_name ?: $client->first_name.' '.$client->last_name)));
        $created = 0;
        $linked = 0;

        foreach ($validated['pick'] as $pick) {
            [$caseId, $name] = array_pad(explode('|', $pick, 2), 2, null);
            $case = LegalCase::query()->find((int) $caseId);
            $name = trim((string) $name);

            if (! $case || $name === '') {
                continue;
            }

            $key = mb_strtolower($name);

            if (! $existing->has($key)) {
                $existing->put($key, Client::create([
                    'client_code' => Client::nextCode(),
                    'client_type' => 'individual',
                    'first_name' => $name,
                    'display_name' => $name,
                    'country' => 'India',
                    'status' => 'active',
                ]));
                $created++;
            }

            $client = $existing->get($key);

            if (! $case->parties()->where('client_id', $client->id)->where('is_client', true)->exists()) {
                $case->parties()->create(['client_id' => $client->id, 'party_name' => $name, 'party_type' => 'Client', 'is_client' => true]);
                $linked++;
            }
        }

        return redirect()->route('clients.index')
            ->with('success', "{$created} new ".str('client')->plural($created).' added, '.$linked.' case '.str('link')->plural($linked).' made. Add their mobile numbers to send updates.');
    }

    /**
     * Open cases without a linked client, each with the names to offer and which one is ours.
     *
     * @return Collection<int, array{case: LegalCase, names: list<array{name: string, ours: bool, label: string}>}>
     */
    private function suggestions(): Collection
    {
        return LegalCase::query()
            ->whereNull('closed_at')
            ->whereDoesntHave('parties', fn ($parties) => $parties->where('is_client', true))
            ->with('parties')
            ->orderBy('case_title')
            ->get()
            ->map(function (LegalCase $case): ?array {
                $sideKnown = filled($case->appearing_for);
                $ourSideMoving = $case->appearsForMovingSide();
                $sides = $case->titleSides();
                $names = collect();

                // Named parties on record come first; otherwise the names in the title.
                foreach ($case->parties as $party) {
                    $moving = LegalCase::isMovingSide(mb_strtolower((string) $party->party_type));
                    $names->push(['name' => $party->party_name, 'ours' => $sideKnown && $moving === $ourSideMoving, 'label' => $party->party_type ?: 'Party']);
                }

                if ($names->isEmpty()) {
                    foreach (['moving' => true, 'answering' => false] as $key => $isMoving) {
                        if ($sides[$key]) {
                            $names->push(['name' => $sides[$key], 'ours' => $sideKnown && $isMoving === $ourSideMoving, 'label' => $isMoving ? 'First party' : 'Opposite party']);
                        }
                    }
                }

                $names = $names->filter(fn (array $option): bool => trim((string) $option['name']) !== '')->unique('name')->values();

                return $names->isEmpty() ? null : ['case' => $case, 'names' => $names->all()];
            })
            ->filter()
            ->values();
    }
}
