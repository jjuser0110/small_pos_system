<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use RecursiveIteratorIterator as Iterator;
use RecursiveDirectoryIterator as DirectoryIterator;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    protected $namespace = 'App\\Http\\Controllers';
    
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });

        $this->mapWebRoutes();
    }

    protected function mapWebRoutes()
    {  
        Route::group([
            'middleware' => 'web',
            'namespace' => $this->namespace
        ], function ($router) {
            // web.php and api.php are registered in boot(); console.php and
            // channels.php are not HTTP routes. Load every other route file.
            $skip = ['web.php', 'api.php', 'console.php', 'channels.php'];

            $iterator = new Iterator(new DirectoryIterator(base_path('routes')), Iterator::SELF_FIRST);
            foreach ($iterator as $file) {
                if ($file->isFile() && !in_array($file->getFilename(), $skip, true)) {
                    // plain require (not require_once) so routes are registered
                    // again whenever the app boots more than once in one process
                    require $file->getPathname();
                }
            }
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
