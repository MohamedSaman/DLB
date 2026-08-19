<?php

namespace App\Services;

use App\Models\ProductDetail;
use App\Models\ProductSupplier;
use App\Models\CategoryList;

class ProductCodeService
{
    /**
     * Generate unique product code (SKU)
     * Format: [CompanyCode][SupplierPrefix][CategoryPrefix]-[0001]
     * Example: DLBSAMBI-0001 or DLBSAMCAR-0001
     */
    public static function generateCode($supplierId = null, $categoryId = null, $companyCode = 'DLB')
    {
        $compPrefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $companyCode ?: 'DLB'));

        // Supplier Prefix (First 3 letters)
        $supplierPrefix = 'GEN';
        if ($supplierId) {
            $supplier = ProductSupplier::find($supplierId);
            if ($supplier && !empty($supplier->name)) {
                $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $supplier->name));
                $supplierPrefix = substr($clean, 0, 3);
            }
        }

        // Category Prefix (First 2-3 letters)
        $categoryPrefix = 'GEN';
        if ($categoryId) {
            $category = CategoryList::find($categoryId);
            if ($category && !empty($category->category_name)) {
                $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $category->category_name));
                // Use 2 letters if length <= 4, otherwise 3 letters
                $length = strlen($clean) <= 4 ? 2 : 3;
                $categoryPrefix = substr($clean, 0, $length);
            }
        }

        $basePrefix = $compPrefix . $supplierPrefix . $categoryPrefix;
        
        // Find highest existing increment number for this prefix pattern
        $existingCodes = ProductDetail::where('code', 'LIKE', $basePrefix . '-%')
            ->pluck('code');

        $maxNumber = 0;
        foreach ($existingCodes as $code) {
            $parts = explode('-', $code);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $num = (int)$lastPart;
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        $nextNumber = str_pad($maxNumber + 1, 4, '0', STR_PAD_LEFT);

        return $basePrefix . '-' . $nextNumber;
    }
}
