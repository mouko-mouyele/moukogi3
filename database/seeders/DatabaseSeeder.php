<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Créer des utilisateurs
        $admin = User::create([
            'name' => 'Administrateur',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $gestionnaire = User::create([
            'name' => 'Gestionnaire',
            'email' => 'gestionnaire@example.com',
            'password' => Hash::make('password'),
            'role' => 'gestionnaire',
        ]);

        $observateur = User::create([
            'name' => 'Observateur',
            'email' => 'observateur@example.com',
            'password' => Hash::make('password'),
            'role' => 'observateur',
        ]);

        // Créer des catégories
        $categorie1 = Category::create([
            'name' => 'Électronique',
            'description' => 'Produits électroniques',
        ]);

        $categorie2 = Category::create([
            'name' => 'Alimentaire',
            'description' => 'Produits alimentaires',
        ]);

        $sousCategorie1 = Category::create([
            'name' => 'Smartphones',
            'description' => 'Téléphones intelligents',
            'parent_id' => $categorie1->id,
        ]);

        // Créer des produits
        $product1 = Product::create([
            'name' => 'iPhone 15',
            'description' => 'Smartphone Apple iPhone 15',
            'barcode' => '1234567890123',
            'supplier' => 'Apple Inc.',
            'price' => 999.99,
            'category_id' => $sousCategorie1->id,
            'stock_minimum' => 10,
            'stock_optimal' => 50,
            'current_stock' => 25,
        ]);

        $product2 = Product::create([
            'name' => 'Samsung Galaxy S24',
            'description' => 'Smartphone Samsung Galaxy S24',
            'barcode' => '1234567890124',
            'supplier' => 'Samsung Electronics',
            'price' => 899.99,
            'category_id' => $sousCategorie1->id,
            'stock_minimum' => 10,
            'stock_optimal' => 50,
            'current_stock' => 5,
        ]);

        $product3 = Product::create([
            'name' => 'Produit Alimentaire A',
            'description' => 'Produit alimentaire de base',
            'barcode' => '1234567890125',
            'supplier' => 'Fournisseur Alimentaire',
            'price' => 5.99,
            'category_id' => $categorie2->id,
            'stock_minimum' => 20,
            'stock_optimal' => 100,
            'current_stock' => 15,
            'expiration_date' => now()->addDays(30),
        ]);

        // Créer des mouvements de stock
        StockMovement::create([
            'product_id' => $product1->id,
            'type' => 'entree',
            'motion_type' => 'achat',
            'quantity' => 50,
            'movement_date' => now()->subDays(30),
            'user_id' => $admin->id,
            'reason' => 'Commande initiale',
        ]);

        StockMovement::create([
            'product_id' => $product1->id,
            'type' => 'sortie',
            'motion_type' => 'vente',
            'quantity' => 25,
            'movement_date' => now()->subDays(15),
            'user_id' => $gestionnaire->id,
            'reason' => 'Vente',
        ]);

        StockMovement::create([
            'product_id' => $product2->id,
            'type' => 'entree',
            'motion_type' => 'achat',
            'quantity' => 30,
            'movement_date' => now()->subDays(20),
            'user_id' => $admin->id,
            'reason' => 'Commande',
        ]);

        StockMovement::create([
            'product_id' => $product2->id,
            'type' => 'sortie',
            'motion_type' => 'vente',
            'quantity' => 25,
            'movement_date' => now()->subDays(10),
            'user_id' => $gestionnaire->id,
            'reason' => 'Vente',
        ]);

        StockMovement::create([
            'product_id' => $product3->id,
            'type' => 'entree',
            'motion_type' => 'achat',
            'quantity' => 100,
            'movement_date' => now()->subDays(25),
            'user_id' => $admin->id,
            'reason' => 'Commande',
        ]);

        StockMovement::create([
            'product_id' => $product3->id,
            'type' => 'sortie',
            'motion_type' => 'vente',
            'quantity' => 85,
            'movement_date' => now()->subDays(5),
            'user_id' => $gestionnaire->id,
            'reason' => 'Vente',
        ]);
    }
}
