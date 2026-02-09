<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== Vérification et création des utilisateurs ===\n\n";

// Supprimer les utilisateurs existants pour les recréer (sauf ceux avec des relations)
$existingUsers = User::whereIn('email', ['admin@example.com', 'gestionnaire@example.com', 'observateur@example.com'])->get();
foreach ($existingUsers as $user) {
    // Vérifier si l'utilisateur a des relations
    $hasMovements = \App\Models\StockMovement::where('user_id', $user->id)->exists();
    $hasInventories = \App\Models\Inventory::where('user_id', $user->id)->exists();
    
    if (!$hasMovements && !$hasInventories) {
        $user->delete();
        echo "Suppression de l'utilisateur: {$user->email}\n";
    } else {
        // Mettre à jour le mot de passe
        $user->password = Hash::make('password');
        $user->save();
        echo "Mot de passe mis à jour pour: {$user->email}\n";
    }
}

// Créer les utilisateurs
$users = [
    [
        'name' => 'Administrateur',
        'email' => 'admin@example.com',
        'password' => 'password',
        'role' => 'admin',
    ],
    [
        'name' => 'Gestionnaire',
        'email' => 'gestionnaire@example.com',
        'password' => 'password',
        'role' => 'gestionnaire',
    ],
    [
        'name' => 'Observateur',
        'email' => 'observateur@example.com',
        'password' => 'password',
        'role' => 'observateur',
    ],
];

foreach ($users as $userData) {
    $existingUser = User::where('email', $userData['email'])->first();
    
    if ($existingUser) {
        // Mettre à jour l'utilisateur existant
        $existingUser->name = $userData['name'];
        $existingUser->password = Hash::make($userData['password']);
        $existingUser->role = $userData['role'];
        $existingUser->save();
        $user = $existingUser;
        echo "✓ Utilisateur mis à jour: {$user->email} ({$user->role})\n";
    } else {
        // Créer un nouvel utilisateur
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
            'role' => $userData['role'],
        ]);
        echo "✓ Utilisateur créé: {$user->email} ({$user->role})\n";
    }
    
    echo "  Mot de passe: {$userData['password']}\n";
    echo "  Hash: " . substr($user->password, 0, 20) . "...\n\n";
}

echo "\n=== Vérification ===\n";
$count = User::count();
echo "Total utilisateurs: {$count}\n\n";

echo "=== Test de connexion ===\n";
$testUser = User::where('email', 'admin@example.com')->first();
if ($testUser && Hash::check('password', $testUser->password)) {
    echo "✓ Le mot de passe 'password' fonctionne pour admin@example.com\n";
} else {
    echo "✗ Problème avec le mot de passe\n";
}

echo "\n=== Comptes disponibles ===\n";
echo "1. admin@example.com / password (Admin)\n";
echo "2. gestionnaire@example.com / password (Gestionnaire)\n";
echo "3. observateur@example.com / password (Observateur)\n";
