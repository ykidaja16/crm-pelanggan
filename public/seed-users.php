<?php

// Security: Only allow local access
$allowedIps = ['127.0.0.1', '::1', 'localhost'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIps) && !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1'])) {
    die('Access denied. This script can only be run locally.');
}

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "<h2>Creating Default Users...</h2>";

try {
    // Create roles if not exist
    $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);
    $userRole = Role::firstOrCreate(['name' => 'User']);

    echo "<p>Roles created/verified:</p>";
    echo "<ul>";
    echo "<li>Super Admin (ID: {$superAdminRole->id})</li>";
    echo "<li>Admin (ID: {$adminRole->id})</li>";
    echo "<li>User (ID: {$userRole->id})</li>";
    echo "</ul>";

    // Create or update users
    $users = [
        [
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@crm.com',
            'password' => bcrypt('password'),
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ],
        [
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@crm.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ],
        [
            'name' => 'User Biasa',
            'username' => 'user',
            'email' => 'user@crm.com',
            'password' => bcrypt('password'),
            'role_id' => $userRole->id,
            'is_active' => true,
        ],
    ];

    echo "<p>Users created/updated:</p>";
    echo "<ul>";

    foreach ($users as $userData) {
        $user = User::updateOrCreate(
            ['username' => $userData['username']],
            $userData
        );
        echo "<li>{$userData['name']} ({$userData['username']}) - Password: password</li>";
    }

    echo "</ul>";

    echo "<h3 style='color: green;'>Success! Default users have been created.</h3>";
    echo "<p>You can now login with:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Username</th><th>Password</th><th>Role</th></tr>";
    echo "<tr><td>superadmin</td><td>password</td><td>Super Admin</td></tr>";
    echo "<tr><td>admin</td><td>password</td><td>Admin</td></tr>";
    echo "<tr><td>user</td><td>password</td><td>User</td></tr>";
    echo "</table>";

    echo "<p><a href='/login'>Go to Login Page</a></p>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
