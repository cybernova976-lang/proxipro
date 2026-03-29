<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

$users = App\Models\User::withTrashed()->get();
echo "=== ALL USERS (including soft-deleted) ===" . PHP_EOL;
foreach ($users as $u) {
    echo "ID: {$u->id} | {$u->email} | deleted_at: " . ($u->deleted_at ?? 'NULL') 
       . " | email_verified_at: " . ($u->email_verified_at ?? 'NULL') 
       . " | pw_starts: " . substr($u->password, 0, 7) 
       . PHP_EOL;
}
echo PHP_EOL . "Total: " . $users->count() . " users" . PHP_EOL;
