<?php

namespace App\Exports;

use App\Models\ProductStock;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryStockReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return ProductStock::with(['product.brand', 'product.category'])->get();
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Model',
            'Brand',
            'Category',
            'Total Stock',
            'Available Stock',
            'Status',
        ];
    }

    public function map($stock): array
    {
        $status = 'In Stock';
        if ($stock->available_stock == 0) {
            $status = 'Out of Stock';
        } elseif ($stock->available_stock < 10) {
            $status = 'Low Stock';
        }

        $productName = $stock->product->name ?? '-';
        if (!empty($stock->variant_value)) {
            $productName .= ' (' . $stock->variant_value . ')';
        }

        return [
            $productName,
            $stock->product->model ?? '-',
            $stock->product->brand->brand_name ?? '-',
            $stock->product->category->category_name ?? '-',
            $stock->total_stock,
            $stock->available_stock,
            $status,
        ];
    }
}
