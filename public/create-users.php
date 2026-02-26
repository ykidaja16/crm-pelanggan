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
use Illuminate\Support\Facades\Hash;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Create Default Users</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>User Database Seeder</h1>";

try {
    // Create roles if not exist
    echo "<h2>Step 1: Creating Roles...</h2>";
    $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);
    $userRole = Role::firstOrCreate(['name' => 'User']);
    
    echo "<div class='success'>✓ Roles created successfully:
    <ul>
        <li>Super Admin (ID: {$superAdminRole->id})</li>
        <li>Admin (ID: {$adminRole->id})</li>
        <li>User (ID: {$userRole->id})</li>
    </ul></div>";

    // Create or update users
    echo "<h2>Step 2: Creating Users...</h2>";
    
    $users = [
        [
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@crm.com',
            'password' => Hash::make('password'),
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ],
        [
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@crm.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ],
        [
            'name' => 'User Biasa',
            'username' => 'user',
            'email' => 'user@crm.com',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
            'is_active' => true,
        ],
    ];

    $createdUsers = [];
    foreach ($users as $userData) {
        $user = User::updateOrCreate(
            ['username' => $userData['username']],
            $userData
        );
        $createdUsers[] = $user;
    }

    echo "<div class='success'>✓ Users created/updated successfully!</div>";
    
    echo "<h2>User Accounts Created:</h2>";
    echo "<table>
        <tr>
            <th>Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Password</th>
            <th>Role</th>
            <th>Status</th>
        </tr>";
    
    foreach ($createdUsers as $user) {
        $roleName = $user->role->name ?? 'N/A';
        echo "<tr>
            <td>{$user->name}</td>
            <td>{$user->username}</td>
            <td>{$user->email}</td>
            <td>password</td>
            <td>{$roleName}</td>
            <td>" . ($user->is_active ? 'Active' : 'Inactive') . "</td>
        </tr>";
    }
    
    echo "</table>";
    
    echo "<div class='success'>
        <h3>✓ Database seeding completed successfully!</h3>
        <p>You can now login with any of the accounts above.</p>
    </div>";
    
    echo "<p>
        <a href='/login' class='btn'>Go to Login Page</a>
        <a href='/dashboard' class='btn'>Go to Dashboard</a>
    </p>";

} catch (Exception $e) {
    echo "<div class='error'>
        <h3>Error occurred:</h3>
        <p>" . $e->getMessage() . "</p>
        <pre>" . $e->getTraceAsString() . "</pre>
    </div>";
}

echo "</body></html>";
