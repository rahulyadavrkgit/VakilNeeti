<?php

namespace App\Http\Controllers;

use App\Models\CaseLink;
use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Links one matter across courts (District Court → High Court → Supreme Court) and connected matters.
 */
class CaseLinkController extends Controller
{
    /**
     * How the chosen case relates to this one.
     *
     * @var array<string, string>
     */
    public const CHOICES = [
        'lower' => 'Lower-court case of this matter',
        'higher' => 'Appeal / revision / SLP of this matter',
        'connected' => 'Connected matter',
    ];

    public function store(Request $request, LegalCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'linked_case_id' => ['required', 'integer', Rule::notIn([$case->id])],
            'link_type' => ['required', Rule::in(array_keys(self::CHOICES))],
        ], [
            'linked_case_id.not_in' => 'Choose a different case.',
        ]);

        // Only the advocate's own cases (the ownership scope applies here).
        $linked = LegalCase::query()->where('user_id', $case->user_id)->findOrFail($validated['linked_case_id']);

        $alreadyLinked = CaseLink::query()
            ->where(fn ($query) => $query->where('case_id', $case->id)->where('linked_case_id', $linked->id))
            ->orWhere(fn ($query) => $query->where('case_id', $linked->id)->where('linked_case_id', $case->id))
            ->exists();

        if ($alreadyLinked) {
            return $this->backToJourney($case, 'These cases are already linked.');
        }

        [$higherCase, $lowerCase, $relation] = match ($validated['link_type']) {
            'lower' => [$case, $linked, 'arises_from'],
            'higher' => [$linked, $case, 'arises_from'],
            default => [$case, $linked, 'connected'],
        };

        CaseLink::create([
            'user_id' => $case->user_id,
            'case_id' => $higherCase->id,
            'linked_case_id' => $lowerCase->id,
            'relation' => $relation,
        ]);

        return $this->backToJourney($case, 'Cases linked.');
    }

    public function destroy(LegalCase $case, CaseLink $link): RedirectResponse
    {
        abort_unless((int) $link->case_id === (int) $case->id || (int) $link->linked_case_id === (int) $case->id, 404);

        $link->delete();

        return $this->backToJourney($case, 'Link removed.');
    }

    private function backToJourney(LegalCase $case, string $message): RedirectResponse
    {
        return redirect()->to(route('cases.show', $case).'#case-journey')->with('success', $message);
    }
}
