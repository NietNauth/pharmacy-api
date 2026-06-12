<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Api\QaController;
use Illuminate\Http\Request;

try {
    $controller = new QaController();
    $request = Request::create('/api/v1/products/019de02b-b48f-716b-8959-f1a908a31c02/qas', 'GET');
    $response = $controller->index('019de02b-b48f-716b-8959-f1a908a31c02', $request);
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response body: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Caught exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
