<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== Création d'un nouvel utilisateur ===\n\n";

// Créer l'utilisateur avec votre email
$user = User::firstOrCreate(
    ['email' => 'moukomoise745@gmail.com'],
    [
        'name' => 'Mouko Moise',
        'password' => Hash::make('password'),
        'role' => 'admin',
    ]
);

// Si l'utilisateur existait déjà, mettre à jour le mot de passe
if ($user->wasRecentlyCreated) {
    echo "✓ Nouvel utilisateur créé !\n";
} else {
    $user->password = Hash::make('password');
    $user->role = 'admin';
    $user->save();
    echo "✓ Utilisateur existant mis à jour !\n";
}

echo "\n=== Informations de connexion ===\n";
echo "Email: {$user->email}\n";
echo "Mot de passe: password\n";
echo "Rôle: {$user->role}\n\n";

echo "✓ Vous pouvez maintenant vous connecter avec ces identifiants !\n";
