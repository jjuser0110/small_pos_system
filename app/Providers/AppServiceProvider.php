<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // money() and other small helpers. Loaded here (as well as by Composer)
        // so they work even where `composer dump-autoload` was not re-run.
        require_once app_path('Support/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // activity log: every record created / changed / deleted / restored, by whom
        foreach (['created', 'updated', 'deleted', 'restored'] as $action) {
            \Illuminate\Support\Facades\Event::listen("eloquent.$action: *", function (string $event, array $data) use ($action) {
                $model = $data[0] ?? null;
                if ($model instanceof \Illuminate\Database\Eloquent\Model) {
                    // a soft delete fires "updated" (deleted_at) before "deleted": the deleted line is enough
                    if ($action === 'updated' && method_exists($model, 'trashed') && $model->wasChanged('deleted_at') && $model->trashed()) {
                        return;
                    }
                    \App\Support\ActivityLogger::modelEvent($action, $model);
                }
            });
        }

        // logins and logouts
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, fn ($e) =>
            \App\Support\ActivityLogger::log('login', $e->user, null, 'Logged in', $e->user));
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, fn ($e) =>
            $e->user ? \App\Support\ActivityLogger::log('logout', $e->user, null, 'Logged out', $e->user) : null);
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class, fn ($e) =>
            \App\Support\ActivityLogger::log('login_failed', null, null,
                'Wrong login for "' . mb_substr((string) ($e->credentials['username'] ?? $e->credentials['email'] ?? ''), 0, 60) . '"'));
    }
}
