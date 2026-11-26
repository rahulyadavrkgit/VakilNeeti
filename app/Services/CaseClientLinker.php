<?php

namespace App\Services;

use App\Models\Client;
use App\Models\LegalCase;

/**
 * The client in a case is the party on the side the advocate appears for. Once that side is known, the party
 * becomes a client by itself (an existing client with the same name is reused) and is linked to the case.
 */
class CaseClientLinker
{
    /**
     * Link the party on our side as the case's client, unless the case already has one or the side is not known.
     */
    public function linkOurParty(LegalCase $case): ?Client
    {
        $name = $this->ourPartyName($case);

        if ($name === null || $case->parties()->where('is_client', true)->exists()) {
            return null;
        }

        return $this->link($case, $name);
    }

    /**
     * Make (or find) the client with this name and link it to the case.
     */
    public function link(LegalCase $case, string $name): Client
    {
        $name = trim($name);
        $client = Client::query()->get()->first(fn (Client $client): bool => mb_strtolower($client->displayName()) === mb_strtolower($name))
            ?? Client::create([
                'client_code' => Client::nextCode(),
                'client_type' => 'individual',
                'first_name' => $name,
                'display_name' => $name,
                'country' => 'India',
                'status' => 'active',
            ]);

        if (! $case->parties()->where('client_id', $client->id)->where('is_client', true)->exists()) {
            $case->parties()->create(['client_id' => $client->id, 'party_name' => $client->displayName(), 'party_type' => 'Client', 'is_client' => true]);
        }

        return $client;
    }

    /**
     * The name of the party we appear for: names typed with the side ("Respondent (Ram Kumar)"), then the parties
     * on record for that side, then that side of the title ("Ram Kumar vs State"). Null when it cannot be told.
     */
    public function ourPartyName(LegalCase $case): ?string
    {
        $knownSide = fn (string $side): bool => LegalCase::isMovingSide($side) || LegalCase::isAnsweringSide($side);
        $side = (string) $case->appearing_for;
        $moving = LegalCase::isMovingSide($side);

        if (! $knownSide($side)) {
            return null;
        }

        // Party numbers ("2", "1-4") say which ones, not who: fall through to the names on record or in the title.
        $typed = trim((string) $case->appearing_for_parties);
        if ($typed !== '' && preg_match('/\p{L}/u', $typed)) {
            return trim(preg_split('/\s*(?:,|&|\band\b)\s*/iu', $typed)[0]) ?: null;
        }

        $party = $case->parties()->where('is_client', false)->get()->first(function ($party) use ($knownSide, $moving): bool {
            $type = mb_strtolower((string) $party->party_type);

            return filled($party->party_name) && $knownSide($type) && LegalCase::isMovingSide($type) === $moving;
        });

        return $party?->party_name ?? $case->titleSides()[$moving ? 'moving' : 'answering'];
    }
}
