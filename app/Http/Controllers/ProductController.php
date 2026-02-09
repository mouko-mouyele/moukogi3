<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|unique:products,barcode',
            'supplier' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'stock_minimum' => 'required|integer|min:0',
            'stock_optimal' => 'required|integer|min:0',
            'expiration_date' => 'nullable|date',
            'technical_sheet' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($request->hasFile('technical_sheet')) {
            $path = $request->file('technical_sheet')->store('technical_sheets', 'public');
            $validated['technical_sheet_path'] = $path;
        }

        $product = Product::create($validated);

        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        $product->load('category', 'stockMovements.user', 'alerts');
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'supplier' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'stock_minimum' => 'required|integer|min:0',
            'stock_optimal' => 'required|integer|min:0',
            'expiration_date' => 'nullable|date',
            'technical_sheet' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($request->hasFile('technical_sheet')) {
            if ($product->technical_sheet_path) {
                Storage::disk('public')->delete($product->technical_sheet_path);
            }
            $path = $request->file('technical_sheet')->store('technical_sheets', 'public');
            $validated['technical_sheet_path'] = $path;
        }

        $product->update($validated);

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        if ($product->technical_sheet_path) {
            Storage::disk('public')->delete($product->technical_sheet_path);
        }

        $product->delete();

        return response()->json(['message' => 'Produit supprimé avec succès']);
    }
}
