<!DOCTYPE html>
<html>
<head>
    <title>Fiche Produit - {{ $product->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #333; }
        .info { margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>{{ $product->name }}</h1>
    
    <div class="info">
        <p><strong>Description:</strong> {{ $product->description ?? '-' }}</p>
        <p><strong>Code-barres:</strong> {{ $product->barcode ?? '-' }}</p>
        <p><strong>Catégorie:</strong> {{ $product->category->name ?? '-' }}</p>
        <p><strong>Stock actuel:</strong> {{ $product->current_stock }}</p>
        <p><strong>Stock minimum:</strong> {{ $product->stock_minimum }}</p>
        <p><strong>Stock optimal:</strong> {{ $product->stock_optimal }}</p>
        <p><strong>Prix:</strong> {{ number_format($product->price, 2, ',', ' ') }} €</p>
    </div>

    <h2>Mouvements récents</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Quantité</th>
                <th>Motif</th>
            </tr>
        </thead>
        <tbody>
            @foreach($product->stockMovements->take(10) as $movement)
            <tr>
                <td>{{ $movement->movement_date->format('d/m/Y') }}</td>
                <td>{{ $movement->type === 'entree' ? 'Entrée' : 'Sortie' }}</td>
                <td>{{ $movement->quantity }}</td>
                <td>{{ $movement->reason ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
