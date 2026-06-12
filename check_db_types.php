<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\Schema;

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$table = 'prescriptions';
$columns = Schema::getColumnListing($table);

echo "Table: $table\n";
foreach ($columns as $column) {
    $type = Schema::getColumnType($table, $column);
    echo " - $column: $type\n";
}
