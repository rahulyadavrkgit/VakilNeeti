<?php

namespace App\Http\Controllers;

use App\Models\CaseParty;
use App\Models\Client;
use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Links clients to a case so they can be sent updates (WhatsApp / email) from the case page.
 */
class CaseClientController extends Controller
{
    public function store(Request $request, LegalCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['nullable', 'integer'],
            'name' => ['required_without:client_id', 'nullable', 'string', 'max:150'],
            'mobile' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'name.required_without' => 'Choose a client or enter a name.',
            'mobile.regex' => 'Enter a valid mobile number.',
        ]);

        if (! empty($validated['client_id'])) {
            $client = Client::query()->findOrFail($validated['client_id']);
        } else {
            if (empty($validated['mobile']) && empty($validated['email'])) {
                throw ValidationException::withMessages(['mobile' => 'Enter a mobile number or an email address.']);
            }

            $client = Client::create([
                'client_code' => Client::nextCode(),
                'client_type' => 'individual',
                'first_name' => $validated['name'],
                'display_name' => $validated['name'],
                'mobile' => $validated['mobile'] ?? null,
                'email' => $validated['email'] ?? null,
                'country' => 'India',
                'status' => 'active',
            ]);
        }

        $alreadyLinked = $case->parties()->where('client_id', $client->id)->where('is_client', true)->exists();

        if (! $alreadyLinked) {
            $case->parties()->create([
                'client_id' => $client->id,
                'party_name' => $client->display_name ?: trim($client->first_name.' '.$client->last_name),
                'party_type' => 'Client',
                'is_client' => true,
            ]);
        }

        return $this->backToClients($case, $alreadyLinked ? 'This client is already linked to the case.' : 'Client linked to the case.');
    }

    public function destroy(LegalCase $case, CaseParty $party): RedirectResponse
    {
        abort_unless($party->case_id === $case->id && $party->is_client, 404);

        // A party added only to notify the client is removed; a real case party just stops being the client.
        $party->party_type === 'Client'
            ? $party->delete()
            : $party->update(['is_client' => false, 'client_id' => null]);

        return $this->backToClients($case, 'Client removed from this case.');
    }

    private function backToClients(LegalCase $case, string $message): RedirectResponse
    {
        return redirect()
            ->to(route('cases.show', $case).'#case-clients')
            ->with('success', $message);
    }
}
