<?php

use DataIntegration\Utils;
use DataIntegration\Listener;

// TODO:
// 数据对接插件应该被拆分成两个插件
// 一个用于游戏内登录插件（Authme 等），直接修改 users 表添加 username 字段即可
// 另一个用于与论坛对接（Discuz 等），需要在数据库中双向同步
return function () {
    Utils::init();

    if (option('da_adapter') == "") return;

    // bind synchronizer to container
    App::bind('synchronizer', function() {
        $classname = "DataIntegration\Synchronizer\\".option('da_adapter');

        if (class_exists($classname)) {
            return new $classname;
        } else {
            option(['da_adapter' => '']);
        }
    });

    App::singleton('db.target', function() {
        $config = unserialize(option('da_connection'));

        $db = new DataIntegration\Database($config);
        return $db->table($config['table'], true);
    });

    App::instance('db.self', DB::table('users'));

    Event::subscribe(Listener\DisableMultiPlayer::class);
    Event::subscribe(Listener\SynchronizeUser::class);
    Event::subscribe(Listener\EncryptPassword::class);

    if (option('da_bilateral')) {
        Event::subscribe(Listener\BilateralSync::class);
    }

};
