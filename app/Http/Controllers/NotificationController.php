<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    /**
     * Mark one notification read and go to the page it points at (always on this site).
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        return redirect()->to($this->pathOnThisSite((string) ($notification->data['url'] ?? '')) ?? route('notifications.index'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * For the phone's service worker after a push: the newest unread alert, to show as the notification.
     */
    public function latest(Request $request): JsonResponse
    {
        $notification = $request->user()->unreadNotifications()->latest()->first();

        return response()->json($notification ? [
            'id' => $notification->id,
            'title' => (string) ($notification->data['title'] ?? 'VakilNiti'),
            'body' => (string) ($notification->data['body'] ?? ''),
            'url' => route('notifications.open', $notification->id),
        ] : null);
    }

    /**
     * This browser wants alerts as push notifications (the person allowed them on the Alerts page).
     */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url:https', 'max:500'],
            'keys.p256dh' => ['nullable', 'string', 'max:255'],
            'keys.auth' => ['nullable', 'string', 'max:255'],
        ]);

        PushSubscription::query()->updateOrCreate(['endpoint' => $data['endpoint']], [
            'user_id' => $request->user()->id,
            'public_key' => $data['keys']['p256dh'] ?? null,
            'auth_token' => $data['keys']['auth'] ?? null,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request): Response
    {
        $request->validate(['endpoint' => ['required', 'string', 'max:500']]);
        $request->user()->pushSubscriptions()->where('endpoint', $request->input('endpoint'))->delete();

        return response()->noContent();
    }

    /**
     * "/cases/7" or "https://vakilniti.com/cases/7#x" → "/cases/7#x"; null for anywhere else.
     */
    private function pathOnThisSite(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || str_starts_with($url, '//') || str_contains($url, '\\')) {
            return null;
        }

        if (isset($parts['host']) && strcasecmp($parts['host'], (string) parse_url(config('app.url'), PHP_URL_HOST)) !== 0
            && strcasecmp($parts['host'], request()->getHost()) !== 0) {
            return null;
        }

        $path = $parts['path'] ?? '/';

        return str_starts_with($path, '/')
            ? $path.(isset($parts['query']) ? '?'.$parts['query'] : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : '')
            : null;
    }
}
