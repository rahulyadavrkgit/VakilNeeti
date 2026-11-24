<?php

namespace App\Http\Controllers;

use App\Models\TeamInvite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Settings → Team: the advocate invites juniors and clerks by email or mobile number, sees who is in the
 * workspace, and removes people; a member can leave.
 */
class TeamController extends Controller
{
    public function invite(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('team', [
            'contact' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(array_keys(User::TEAM_ROLES))],
        ]);

        $owner = $request->user();
        $contact = trim($validated['contact']);

        // An email, or a 10-digit Indian mobile number.
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $key = ['email' => mb_strtolower($contact)];
            $isOwn = $key['email'] === mb_strtolower((string) $owner->email);
        } elseif (preg_match('/^(\+?91)?[\s-]*[6-9](\d[\s-]*){9}$/', $contact)) {
            $key = ['mobile' => '+91'.substr(preg_replace('/\D+/', '', $contact) ?? '', -10)];
            $isOwn = $key['mobile'] === $owner->mobile;
        } else {
            return back()->withErrors(['contact' => 'Enter an email address or a 10-digit Indian mobile number.'], 'team')->withInput();
        }

        if ($isOwn) {
            return back()->withErrors(['contact' => 'That is your own account.'], 'team')->withInput();
        }

        TeamInvite::updateOrCreate(
            ['owner_id' => $owner->id] + $key,
            ['role' => $validated['role'], 'accepted_user_id' => null, 'accepted_at' => null],
        );

        // Someone who already uses VakilNiti alone joins on their next sign-in.
        return redirect()->to(route('profile.index').'#team')
            ->with('team_success', 'Invited. They join your workspace when they sign in'.(isset($key['email']) ? ' with '.$key['email'] : ' with an account that has the number '.$key['mobile']).'.');
    }

    public function cancelInvite(Request $request, TeamInvite $invite): RedirectResponse
    {
        abort_unless($invite->owner_id === $request->user()->id && $invite->accepted_at === null, 404);
        $invite->delete();

        return redirect()->to(route('profile.index').'#team')->with('team_success', 'Invite cancelled.');
    }

    public function remove(Request $request, User $member): RedirectResponse
    {
        abort_unless($member->team_owner_id === $request->user()->id, 404);

        $member->forceFill(['team_owner_id' => null, 'team_role' => null])->save();
        TeamInvite::where('owner_id', $request->user()->id)->where('accepted_user_id', $member->id)->delete();

        return redirect()->to(route('profile.index').'#team')->with('team_success', $member->name.' was removed from your team.');
    }

    public function changeRole(Request $request, User $member): RedirectResponse
    {
        abort_unless($member->team_owner_id === $request->user()->id, 404);
        $validated = $request->validate(['role' => ['required', Rule::in(array_keys(User::TEAM_ROLES))]]);
        $member->forceFill(['team_role' => $validated['role']])->save();

        return redirect()->to(route('profile.index').'#team')->with('team_success', $member->name.' is now a '.User::TEAM_ROLES[$validated['role']].'.');
    }

    public function leave(Request $request): RedirectResponse
    {
        $user = $request->user();
        TeamInvite::where('owner_id', $user->team_owner_id)->where('accepted_user_id', $user->id)->delete();
        $user->forceFill(['team_owner_id' => null, 'team_role' => null])->save();

        return redirect()->route('dashboard')->with('auth_login_success', 'You left the team. You now see your own workspace.');
    }
}
