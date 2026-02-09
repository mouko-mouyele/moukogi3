<!DOCTYPE html>
<html>
<head>
    <title>Inventaire {{ $inventory->reference }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>Inventaire {{ $inventory->reference }}</h1>
    <p><strong>Date:</strong> {{ $inventory->inventory_date->format('d/m/Y') }}</p>
    <p><strong>Réalisé par:</strong> {{ $inventory->user->name }}</p>
    
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Quantité théorique</th>
                <th>Quantité réelle</th>
                <th>Écart</th>
                <th>Justification</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inventory->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->theoretical_quantity }}</td>
                <td>{{ $item->actual_quantity }}</td>
                <td>{{ $item->difference }}</td>
                <td>{{ $item->justification ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
