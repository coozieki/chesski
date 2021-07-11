<?php

use App\Console\Kernel;
use App\Http\Controllers\SocketServer;

if (file_exists(__DIR__.'/../storage/framework/maintenance.php')) {
    require __DIR__.'/../storage/framework/maintenance.php';
}

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

set_time_limit(0);

ob_implicit_flush();

$socketServer = new SocketServer();

$socketServer->init();
