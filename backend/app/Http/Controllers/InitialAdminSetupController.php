<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class InitialAdminSetupController extends Controller
{
    private function ensureSetupAllowed(Request $request): void
    {
        abort_unless(app()->environment('local'), 404);
        abort_unless(in_array($request->ip(), ['127.0.0.1', '::1'], true), 404);
        abort_if(User::where('is_admin', true)->exists(), 404);
    }

    public function show(Request $request): View
    {
        $this->ensureSetupAllowed($request);

        return view('auth.initial-admin-setup');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSetupAllowed($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                $user = new User();
                $user->email = $validated['email'];
            }

            $user->name = $validated['name'];
            $user->password = $validated['password'];
            $user->save();

            $user->forceFill(['is_admin' => true])->save();
        });

        return redirect()
            ->route('admin.login')
            ->with('status', 'Administrator account created successfully. You can now sign in.');
    }
}