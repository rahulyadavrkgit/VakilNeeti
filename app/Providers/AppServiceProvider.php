<?php

namespace App\Providers;

use App\Services\CourtData\CourtDataProviderRegistry;
use App\Services\ReminderService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            CourtDataProviderRegistry::class,
            function ($app) {
                return new CourtDataProviderRegistry(
                    $app,
                    config('court-data.providers', [])
                );
            }
        );
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        // @money($amount) prints an amount in the signed-in user's currency, e.g. "Rs. 1,500.00".
        Blade::directive('money', fn (string $amount): string => "<?php echo e(\App\Enums\Currency::current()->format({$amount})); ?>");

        // @currency prints the signed-in user's currency symbol, e.g. "Rs.".
        Blade::directive('currency', fn (): string => "<?php echo e(\App\Enums\Currency::current()->symbol()); ?>");

        // Header bell: send any due reminders, then share the latest notifications with the layout.
        View::composer('layouts.app', function (\Illuminate\View\View $view): void {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            app(ReminderService::class)->sendIfDue($user);

            $view->with([
                'bellNotifications' => $user->notifications()->limit(8)->get(),
                'bellUnreadCount' => $user->unreadNotifications()->count(),
            ]);
        });
    }
}
