# Application de Gestion de Stock avec Prédiction

Application web intelligente pour la gestion complète des stocks avec prédiction automatisée des besoins futurs.

## Fonctionnalités

### 🔐 Authentification et Sécurité
- Connexion/Déconnexion avec JWT (Laravel Sanctum)
- Gestion des rôles :
  - **Admin** : Accès complet
  - **Gestionnaire** : Gestion des produits et mouvements
  - **Observateur** : Lecture seule
- Mots de passe hashés avec BCrypt

### 📦 Module Produits
- Ajout, modification, suppression de produits
- Gestion des catégories (hiérarchique)
- Définition des niveaux de stock (minimum, optimal)
- Upload de fiches techniques
- Gestion des codes-barres et fournisseurs

### 📊 Module Mouvements de Stock
- Entrées (achat, retour, correction)
- Sorties (vente, perte, casse, expiration)
- Historique détaillé avec filtres
- Mise à jour automatique du stock

### 📋 Module Inventaires
- Réalisation d'inventaires physiques
- Saisie des quantités constatées
- Ajustement automatique avec justification
- Archivage des inventaires

### 🔮 Module Prédiction
- **Régression linéaire** : Pour données > 100 lignes
- **Moyenne mobile** : Pour données intermédiaires
- **Prédiction simple** : Pour données limitées
- Estimation de rupture probable
- Recommandations de commande
- Graphiques d'évolution

### ⚠️ Module Alertes
- Stock minimum atteint
- Rupture imminente (basée sur prédiction)
- Expiration proche
- Surstock détecté
- Alertes visibles sur le tableau de bord

### 📈 Tableau de bord
- Statistiques clés (produits, valeur, alertes)
- Produits proches de la rupture
- Mouvements récents
- Graphiques d'évolution
- Taux de rotation

### 📄 Exports
- Export Excel : Produits, Mouvements
- Export PDF : Inventaires, Fiches produits

## Technologies

### Backend
- **Laravel 10** : Framework PHP
- **MySQL/PostgreSQL** : Base de données
- **Laravel Sanctum** : Authentification API
- **PhpSpreadsheet** : Export Excel
- **DomPDF** : Export PDF

### Frontend
- **Vue.js 3** : Framework JavaScript
- **Vue Router** : Navigation
- **Tailwind CSS** : Styling
- **Axios** : Requêtes HTTP
- **Chart.js** : Graphiques (optionnel)

## Installation

### Prérequis
- PHP >= 8.1
- Composer
- Node.js et npm
- MySQL ou PostgreSQL

### Étapes d'installation

1. **Cloner le projet**
```bash
cd moukogi3
```

2. **Installer les dépendances PHP**
```bash
composer install
```

3. **Installer les dépendances JavaScript**
```bash
npm install
```

4. **Configurer l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configurer la base de données dans `.env`**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moukogi3
DB_USERNAME=root
DB_PASSWORD=
```

6. **Exécuter les migrations**
```bash
php artisan migrate
```

7. **Remplir la base de données avec des données de test**
```bash
php artisan db:seed
```

8. **Compiler les assets**
```bash
npm run build
# ou pour le développement
npm run dev
```

9. **Démarrer le serveur**
```bash
php artisan serve
```

L'application sera accessible sur `http://localhost:8000`

## Comptes par défaut

Après avoir exécuté les seeders, vous pouvez vous connecter avec :

- **Admin** : admin@example.com / password
- **Gestionnaire** : gestionnaire@example.com / password
- **Observateur** : observateur@example.com / password

## Structure de l'API

Toutes les routes API sont préfixées par `/api`

### Authentification
- `POST /api/login` - Connexion
- `POST /api/logout` - Déconnexion
- `GET /api/me` - Informations utilisateur

### Produits
- `GET /api/products` - Liste des produits
- `POST /api/products` - Créer un produit (Gestionnaire+)
- `GET /api/products/{id}` - Détails d'un produit
- `PUT /api/products/{id}` - Modifier un produit (Gestionnaire+)
- `DELETE /api/products/{id}` - Supprimer un produit (Gestionnaire+)
- `GET /api/products/{id}/predict` - Prédiction pour un produit
- `GET /api/products/{id}/chart-data` - Données graphiques

### Catégories
- `GET /api/categories` - Liste des catégories
- `POST /api/categories` - Créer une catégorie (Gestionnaire+)
- `GET /api/categories/{id}` - Détails d'une catégorie
- `PUT /api/categories/{id}` - Modifier une catégorie (Gestionnaire+)
- `DELETE /api/categories/{id}` - Supprimer une catégorie (Gestionnaire+)

### Mouvements
- `GET /api/stock-movements` - Liste des mouvements
- `POST /api/stock-movements` - Créer un mouvement (Gestionnaire+)
- `GET /api/stock-movements/{id}` - Détails d'un mouvement
- `PUT /api/stock-movements/{id}` - Modifier un mouvement (Gestionnaire+)
- `DELETE /api/stock-movements/{id}` - Supprimer un mouvement (Gestionnaire+)

### Inventaires
- `GET /api/inventories` - Liste des inventaires
- `POST /api/inventories` - Créer un inventaire (Gestionnaire+)
- `GET /api/inventories/{id}` - Détails d'un inventaire
- `PUT /api/inventories/{id}` - Modifier un inventaire (Gestionnaire+)
- `DELETE /api/inventories/{id}` - Supprimer un inventaire (Gestionnaire+)

### Alertes
- `GET /api/alerts` - Liste des alertes
- `GET /api/alerts/{id}` - Détails d'une alerte
- `POST /api/alerts/{id}/resolve` - Résoudre une alerte (Gestionnaire+)
- `POST /api/alerts/{id}/dismiss` - Ignorer une alerte (Gestionnaire+)
- `POST /api/alerts/check-all` - Vérifier toutes les alertes (Gestionnaire+)

### Dashboard
- `GET /api/dashboard` - Données du tableau de bord

### Exports
- `GET /api/export/products/excel` - Export Excel des produits
- `GET /api/export/movements/excel` - Export Excel des mouvements
- `GET /api/export/inventories/{id}/pdf` - Export PDF d'un inventaire
- `GET /api/export/products/{id}/pdf` - Export PDF d'un produit

## Algorithme de Prédiction

Le système utilise trois méthodes selon la quantité de données disponibles :

1. **Prédiction Simple** : Moins de 2 mouvements
   - Basée sur le stock actuel et une estimation de consommation

2. **Moyenne Mobile** : 2 à 100 mouvements
   - Calcule la consommation quotidienne moyenne
   - Projette sur la période demandée

3. **Régression Linéaire** : Plus de 100 mouvements
   - Analyse la tendance des consommations
   - Prédit en fonction de la tendance

## Tests

```bash
php artisan test
```

## Développement

Pour le développement avec rechargement automatique :

```bash
# Terminal 1 : Serveur Laravel
php artisan serve

# Terminal 2 : Vite (compilation des assets)
npm run dev
```

## Sécurité

- Mots de passe hashés avec BCrypt
- Protection CSRF activée
- Validation des données (OWASP)
- Authentification JWT avec Laravel Sanctum
- Middleware de rôles pour les permissions

## Licence

MIT

## Auteur

Développé pour le projet de gestion de stock avec prédiction
