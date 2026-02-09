<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    public function exportProductsExcel()
    {
        $products = Product::with('category')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // En-têtes
        $sheet->setCellValue('A1', 'Nom');
        $sheet->setCellValue('B1', 'Code-barres');
        $sheet->setCellValue('C1', 'Catégorie');
        $sheet->setCellValue('D1', 'Stock Actuel');
        $sheet->setCellValue('E1', 'Stock Minimum');
        $sheet->setCellValue('F1', 'Stock Optimal');
        $sheet->setCellValue('G1', 'Prix');
        $sheet->setCellValue('H1', 'Valeur');

        // Style des en-têtes
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Données
        $row = 2;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product->name);
            $sheet->setCellValue('B' . $row, $product->barcode ?? '');
            $sheet->setCellValue('C' . $row, $product->category->name ?? '');
            $sheet->setCellValue('D' . $row, $product->current_stock);
            $sheet->setCellValue('E' . $row, $product->stock_minimum);
            $sheet->setCellValue('F' . $row, $product->stock_optimal);
            $sheet->setCellValue('G' . $row, $product->price);
            $sheet->setCellValue('H' . $row, $product->current_stock * $product->price);
            $row++;
        }

        // Ajuster la largeur des colonnes
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'produits_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function exportMovementsExcel(Request $request)
    {
        $query = StockMovement::with('product', 'user');

        if ($request->has('date_from')) {
            $query->where('movement_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('movement_date', '<=', $request->date_to);
        }

        $movements = $query->orderBy('movement_date', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // En-têtes
        $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Produit');
        $sheet->setCellValue('C1', 'Type');
        $sheet->setCellValue('D1', 'Quantité');
        $sheet->setCellValue('E1', 'Utilisateur');
        $sheet->setCellValue('F1', 'Motif');

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ]
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($movements as $movement) {
            $sheet->setCellValue('A' . $row, $movement->movement_date);
            $sheet->setCellValue('B' . $row, $movement->product->name);
            $sheet->setCellValue('C' . $row, $movement->type === 'entree' ? 'Entrée' : 'Sortie');
            $sheet->setCellValue('D' . $row, $movement->quantity);
            $sheet->setCellValue('E' . $row, $movement->user->name);
            $sheet->setCellValue('F' . $row, $movement->reason ?? '');
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'mouvements_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function exportInventoryPdf(Inventory $inventory)
    {
        $inventory->load('items.product', 'user');
        
        $pdf = Pdf::loadView('exports.inventory', compact('inventory'));
        return $pdf->download('inventaire_' . $inventory->reference . '.pdf');
    }

    public function exportProductPdf(Product $product)
    {
        $product->load('category', 'stockMovements');
        
        $pdf = Pdf::loadView('exports.product', compact('product'));
        return $pdf->download('produit_' . $product->id . '.pdf');
    }
}
