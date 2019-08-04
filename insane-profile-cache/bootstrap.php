<?php

use App\Models\Player;
use App\Services\Hook;
use App\Exceptions\PrettyPageException;
use Illuminate\Contracts\Events\Dispatcher;
use InsaneProfileCache\Listener\DeleteFileCache;
use InsaneProfileCache\Listener\UpdateFileCache;

require __DIR__.'/src/common_functions.php';

define('PROFILE_CACHE_PATH', realpath(__DIR__.'/cache'));

foreach (['csl', 'usm'] as $apiType) {
    if (!PROFILE_CACHE_PATH || !is_writable(PROFILE_CACHE_PATH."/$apiType")) {
        throw new PrettyPageException("[插件错误][insane-profile-cache] 没有对文件缓存目录 /cache/$apiType 的写入权限");
    }
}

return function (Dispatcher $events) {
    if (option('enable_json_cache')) {
        option(['enable_json_cache' => false]);
    }

    $events->subscribe(UpdateFileCache::class);
    $events->subscribe(DeleteFileCache::class);

    $events->listen('Illuminate\Console\Events\ArtisanStarting', function ($event) {
        $event->artisan->resolveCommands([
            'InsaneProfileCache\Commands\Clean',
            'InsaneProfileCache\Commands\Generate'
        ]);
    });

    Hook::addRoute(function ($router) {

        $router->get('/admin/generate-profile-cache', function () {

            if (isset($_GET['continue'])) {
                // Delete all cache file first
                cleanProfileFileCache();

                $indicator = 0;

                foreach (Player::all() as $player) {
                    generateProfileFileCache($player);

                    $indicator++;
                }

                return "在目录 ".PROFILE_CACHE_PATH." 下成功生成了 $indicator 个缓存文件。";
            }

            return view('InsaneProfileCache::generate');
        })->middleware(['web', 'auth', 'admin']);

    });
};
