<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

Config::set('database.connections.sqlite.database', __DIR__ . '/database/database.sqlite');
DB::purge('sqlite');

$tables = DB::connection('sqlite')->getSchemaBuilder()->getTableListing();
print_r($tables);
