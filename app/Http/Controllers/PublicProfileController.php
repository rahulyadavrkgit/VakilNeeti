<?php

namespace App\Http\Controllers;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The one-page public CV. Its content comes from the details already given in profile setup;
 * the owner only picks the link, an optional introduction, which contact details to show, and whether it is live.
 */
class PublicProfileController extends Controller
{
    /** Usernames that could be mistaken for the platform itself. */
    private const RESERVED_USERNAMES = [
        'admin', 'administrator', 'api', 'app', 'billing', 'cv', 'download', 'help', 'login', 'logout',
        'official', 'privacy', 'profile', 'register', 'root', 'settings', 'staff', 'support', 'team',
        'terms', 'vakilniti', 'vakil-niti', 'www',
    ];

    public function edit(Request $request): View
    {
        $user = $this->individual($request->user());
        $cv = $this->profile($user);

        return view('profile.cv-edit', [
            'cv' => $cv,
            'settings' => $this->settings($user),
            'username' => $user->public_cv_username ?? $this->suggestedUsername($cv['name']),
            'isPublished' => $user->public_cv_published_at !== null,
            'publicUrl' => $user->public_cv_published_at ? route('cv.show', $user->public_cv_username) : null,
            'missing' => $this->missingDetails($cv),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->individual($request->user());
        if (is_string($request->input('username'))) {
            $request->merge(['username' => Str::lower(trim($request->input('username')))]);
        }

        $usernameFormat = 'Use only lowercase letters, numbers and single hyphens, starting and ending with a letter or number.';
        $data = $request->validate([
            'username' => [
                'required_if_accepted:publish', 'nullable', 'string', 'min:3', 'max:30',
                'regex:/\A[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\z/', 'not_regex:/--/',
                Rule::notIn(self::RESERVED_USERNAMES),
                Rule::unique('users', 'public_cv_username')->ignore($user->id),
            ],
            'about' => ['nullable', 'string', 'max:1500'],
            'show_email' => ['required', 'boolean'],
            'show_phone' => ['required', 'boolean'],
            'publish' => ['required', 'boolean'],
        ], [
            'username.required_if_accepted' => 'Choose a username for your page link before publishing.',
            'username.regex' => $usernameFormat,
            'username.not_regex' => $usernameFormat,
            'username.not_in' => 'That username is reserved. Please choose another.',
            'username.unique' => 'That username is already taken. Please choose another.',
        ]);

        $user->forceFill([
            'public_cv' => [
                'about' => filled($data['about'] ?? null) ? trim($data['about']) : null,
                'show_email' => (bool) $data['show_email'],
                'show_phone' => (bool) $data['show_phone'],
            ],
            'public_cv_username' => $data['username'] ?? null,
            'public_cv_published_at' => $data['publish'] ? ($user->public_cv_published_at ?? now()) : null,
        ])->save();

        return redirect()->route('profile.cv.edit')
            ->with('cv_saved', $data['publish'] ? 'Saved. Your page is live.' : 'Saved. Your page is not public.');
    }

    public function preview(Request $request): Response
    {
        return $this->page($this->individual($request->user()), false);
    }

    public function download(Request $request): Response
    {
        return $this->pdf($this->individual($request->user()));
    }

    public function show(string $username): Response
    {
        return $this->page($this->published($username), true);
    }

    public function publicDownload(string $username): Response
    {
        return $this->pdf($this->published($username));
    }

    private function individual(User $user): User
    {
        abort_if($user->account_type === 'company', 404);

        return $user;
    }

    private function published(string $username): User
    {
        $user = User::query()
            ->where('public_cv_username', Str::lower($username))
            ->whereNotNull('public_cv_published_at')
            ->whereNull('suspended_at')
            ->firstOrFail();

        return $this->individual($user);
    }

    private function page(User $user, bool $public): Response
    {
        $cv = $this->profile($user);
        $shareUrl = $user->public_cv_published_at ? route('cv.show', $user->public_cv_username) : null;

        return response()->view('profile.cv-page', [
            'cv' => $cv,
            'isOwnerPreview' => ! $public,
            'isEmbedded' => ! $public && request()->boolean('embed'),
            'shareUrl' => $shareUrl,
            'downloadUrl' => $public ? route('cv.download', $user->public_cv_username) : route('profile.cv.download'),
            'missing' => $public ? [] : $this->missingDetails($cv),
            'backUrl' => $this->backUrl($user),
        ], 200, $this->headers());
    }

    /**
     * Where Back leads: the owner returns to their settings; a visitor who came from another VakilNiti page goes back
     * there. Someone who opened a shared link from outside gets no Back button, as there is nowhere on the site to return to.
     */
    private function backUrl(User $user): ?string
    {
        if (request()->user()?->is($user)) {
            return route('profile.cv.edit');
        }

        $referrer = (string) request()->headers->get('referer');

        return $referrer !== '' && parse_url($referrer, PHP_URL_HOST) === request()->getHost() ? url('/') : null;
    }

    private function pdf(User $user): Response
    {
        $cv = $this->profile($user);
        $name = Str::limit(Str::slug($cv['name']), 100, '') ?: 'profile';
        $cv['photo_data'] = $cv['photo_path'] ? $this->photoDataUri($cv['photo_path']) : null;
        $response = Pdf::loadView('profile.cv-pdf', ['cv' => $cv, 'shareUrl' => $user->public_cv_published_at ? route('cv.show', $user->public_cv_username) : null])
            ->setPaper('a4')->download($name.'-cv.pdf');

        $response->headers->add($this->headers());

        return $response;
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow, noarchive'];
    }

    /** @return array{about: ?string, show_email: bool, show_phone: bool} */
    private function settings(User $user): array
    {
        $saved = is_array($user->public_cv) ? $user->public_cv : [];
        $about = is_string($saved['about'] ?? null) ? $saved['about'] : null;

        return [
            'about' => $about,
            'show_email' => (bool) ($saved['show_email'] ?? true),
            'show_phone' => (bool) ($saved['show_phone'] ?? false),
        ];
    }

    /**
     * Everything shown on the CV, read live from the profile setup details.
     *
     * @return array{name: string, initials: string, title: string, location: ?string, photo_url: ?string, photo_path: ?string, about: ?string, practice: list<string>, courts: list<string>, education: list<string>, memberships: list<string>, facts: array<string, string>, contacts: list<array{type: string, label: string, href: string}>, updated: ?string}
     */
    private function profile(User $user): array
    {
        $settings = $this->settings($user);
        $advocate = $user->advocateProfile;
        $personal = DB::table('personal_profiles')->where('user_id', $user->id)->latest('updated_at')->first();
        $professional = DB::table('professional_profiles')->where('user_id', $user->id)->latest('updated_at')->first();
        $website = DB::table('profile_contacts')->where('user_id', $user->id)->where('contact_type', 'website')
            ->where('is_active', true)->value('contact_value');

        $name = trim((string) ($personal?->full_name ?: $user->name)) ?: 'Advocate';
        $years = $advocate?->years_experience;
        if ($years === null && $professional?->practice_start_date) {
            $years = (int) now()->diffInYears($professional->practice_start_date, true);
        }

        $facts = array_filter([
            'Experience' => $years !== null ? ($years < 1 ? 'Under 1 year' : $years.' '.Str::plural('year', $years).' in practice') : null,
            'Enrolment' => $advocate?->bar_council_registration_number ?: $professional?->enrolment_number,
            'Bar Council' => $professional?->bar_council_name ?: ($advocate?->bar_state ? 'Bar Council of '.$advocate->bar_state : null),
            'Supreme Court AOR' => $professional?->supreme_court_aor_number,
            'Languages' => implode(', ', array_filter((array) ($advocate?->languages_spoken ?? []))),
        ], 'filled');

        $education = $this->lines($professional?->qualifications);
        if ($education === [] && $advocate?->graduation_year) {
            $education = ['Law graduate, '.$advocate->graduation_year];
        }

        $photoPath = $advocate?->photo_path;
        $hasPhoto = $photoPath && Storage::disk('public')->exists($photoPath);

        $email = $personal?->email ?: $user->email;
        $phone = $personal?->phone ?: $user->mobile;
        $contacts = [];
        if ($settings['show_email'] && filled($email)) {
            $contacts[] = ['type' => 'email', 'label' => $email, 'href' => 'mailto:'.$email];
        }
        if ($settings['show_phone'] && filled($phone)) {
            $contacts[] = ['type' => 'phone', 'label' => $phone, 'href' => 'tel:'.preg_replace('/[^+0-9]/', '', $phone)];
        }
        if (filled($website) && preg_match('#\Ahttps?://#i', $website)) {
            $contacts[] = ['type' => 'website', 'label' => preg_replace('#\Ahttps?://(www\.)?#i', '', rtrim($website, '/')), 'href' => $website];
        }

        return [
            'name' => $name,
            'initials' => $this->initials($name),
            'title' => $personal?->professional_title ?: 'Advocate',
            'location' => implode(', ', array_unique(array_filter([
                $advocate?->city ?: $personal?->city,
                $advocate?->region ?: $personal?->state,
            ]))) ?: null,
            'photo_url' => $hasPhoto ? Storage::disk('public')->url($photoPath) : null,
            'photo_path' => $hasPhoto ? $photoPath : null,
            'about' => $settings['about'] ?: ($professional?->professional_bio ?: null),
            'practice' => $this->items(array_merge((array) ($advocate?->specializations ?? []), $this->split($professional?->practice_areas ?: $advocate?->practice_areas))),
            'courts' => $this->items(array_merge($this->split($professional?->courts_of_practice), [$advocate?->court])),
            'education' => $education,
            'memberships' => $this->lines($professional?->professional_memberships),
            'facts' => $facts,
            'contacts' => $contacts,
            'updated' => collect([$advocate?->updated_at, $professional?->updated_at, $user->updated_at])->filter()
                ->map(fn (mixed $date): Carbon => Carbon::parse($date))->max()?->format('F Y'),
        ];
    }

    /**
     * What is still empty, with where to fill it.
     *
     * @param  array<string, mixed>  $cv
     * @return list<string>
     */
    private function missingDetails(array $cv): array
    {
        return array_keys(array_filter([
            'photo' => ! $cv['photo_url'],
            'practice areas' => $cv['practice'] === [],
            'court' => $cv['courts'] === [],
            'experience' => ! isset($cv['facts']['Experience']),
            'bar enrolment' => ! isset($cv['facts']['Enrolment']),
        ]));
    }

    /** @return list<string> */
    private function split(?string $text): array
    {
        return $text ? preg_split('/\s*(?:[,;\n]|\r\n)\s*/', $text) : [];
    }

    /** @return list<string> */
    private function lines(?string $text): array
    {
        return $text ? $this->items(preg_split('/\R+/', $text)) : [];
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<string>
     */
    private function items(array $items): array
    {
        return (new Collection($items))
            ->map(fn (mixed $item): string => trim((string) $item, " \t-•*"))
            ->filter()
            ->unique(fn (string $item): string => Str::lower($item))
            ->values()
            ->all();
    }

    private function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim(preg_replace('/^(adv\.?|advocate)\s+/i', '', $name)));

        return Str::upper(implode('', array_map(fn (string $word): string => mb_substr($word, 0, 1), array_slice(array_filter($words), 0, 2))));
    }

    /** The PDF renderer cannot fetch URLs, so the photo is embedded (JPEG and PNG only). */
    private function photoDataUri(string $path): ?string
    {
        $mime = Storage::disk('public')->mimeType($path);
        if (! in_array($mime, ['image/jpeg', 'image/png'], true) || Storage::disk('public')->size($path) > 4 * 1024 * 1024) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
    }

    private function suggestedUsername(string $name): string
    {
        $base = trim(Str::limit(Str::slug(preg_replace('/^(adv\.?|advocate)\s+/i', '', $name)), 30, ''), '-');

        if (strlen($base) < 3 || in_array($base, self::RESERVED_USERNAMES, true)) {
            return '';
        }

        return User::query()->where('public_cv_username', $base)->exists() ? '' : $base;
    }
}
