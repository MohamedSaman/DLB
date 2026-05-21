<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;
use App\Models\ProductDetail;
use App\Models\ProductPrice;
use Exception;

#[Title("Update Price")]
class UpdatePrice extends Component
{
    use WithFileUploads;
    use WithDynamicLayout;

    public $uploadedFile;
    public $importedData = [];
    public $importErrors = [];
    public $importSuccess = false;
    public $showImportPreview = false;
    public $perPage = 10;
    public $searchTerm = '';
    public $currentPage = 1;

    protected $rules = [
        'uploadedFile' => 'required|file|mimes:csv,xlsx,xls|max:5120',
    ];

    public function updatedUploadedFile()
    {
        $this->validateOnly('uploadedFile');
    }

    public function render()
    {
        $products = $this->getProductsPriceData();
        return view('livewire.admin.update-price', compact('products'))->layout($this->layout);
    }

    /**
     * Get all products with their variants and prices
     */
    private function getProductsPriceData()
    {
        $query = ProductDetail::with(['variant', 'prices'])
            ->where('status', 'active');

        $products = $query->orderBy('code', 'asc')->get();

        // Format data with variants
        $formattedData = [];
        foreach ($products as $product) {
            if ($product->hasVariants() && $product->variant) {
                // Product has variants - get prices for each variant
                $variantPrices = $product->prices()->where('variant_id', '!=', null)->get();

                foreach ($variantPrices as $price) {
                    $formattedData[] = [
                        'id' => $product->id . '_' . $price->id,
                        'product_id' => $product->id,
                        'product_code' => $product->code,
                        'product_name' => $product->name,
                        'variant_name' => $product->variant->variant_name ?? '',
                        'variant_value' => $price->variant_value ?? '',
                        'full_name' => $product->name . ' - ' . ($product->variant->variant_name ?? '') . ': ' . ($price->variant_value ?? ''),
                        'wholesale_price' => $price->wholesale_price ?? 0,
                        'distributor_price' => $price->distributor_price ?? 0,
                        'retail_price' => $price->retail_price ?? 0,
                        'has_variant' => true,
                        'price_id' => $price->id,
                    ];
                }
            } else {
                // Product has no variants - get single price
                $price = $product->prices()->where('variant_id', null)->first();
                if ($price) {
                    $formattedData[] = [
                        'id' => $product->id . '_' . $price->id,
                        'product_id' => $product->id,
                        'product_code' => $product->code,
                        'product_name' => $product->name,
                        'variant_name' => '',
                        'variant_value' => '',
                        'full_name' => $product->name,
                        'wholesale_price' => $price->wholesale_price ?? 0,
                        'distributor_price' => $price->distributor_price ?? 0,
                        'retail_price' => $price->retail_price ?? 0,
                        'has_variant' => false,
                        'price_id' => $price->id,
                    ];
                }
            }
        }

        $collection = collect($formattedData);

        // Support searching by mixed terms, e.g. "Test 6" (name + variant value).
        $searchText = mb_strtolower(trim((string) $this->searchTerm));
        if ($searchText !== '') {
            $terms = preg_split('/\s+/', $searchText, -1, PREG_SPLIT_NO_EMPTY);
            $collection = $collection->filter(function ($row) use ($terms) {
                $haystack = mb_strtolower(trim(implode(' ', [
                    $row['product_code'] ?? '',
                    $row['product_name'] ?? '',
                    $row['variant_name'] ?? '',
                    $row['variant_value'] ?? '',
                    $row['full_name'] ?? '',
                ])));

                foreach ($terms as $term) {
                    if (!str_contains($haystack, $term)) {
                        return false;
                    }
                }

                return true;
            })->values();
        }

        // Paginate the formatted data
        $total = $collection->count();
        $items = $collection->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $this->perPage,
            $this->currentPage,
            [
                'path' => url('/admin/update-price'),
                'query' => request()->query(),
            ]
        );
    }

    /**
    /**
     * Export current prices to CSV
     */
    public function exportCSV()
    {
        $products = ProductDetail::with(['variant', 'prices'])
            ->where('status', 'active')
            ->orderBy('code', 'asc')
            ->get();

        $csvData = [];
        $csvData[] = ['Price ID', 'Product Code', 'Product Name + Variant Value', 'Wholesale Price', 'Distributor Price', 'Retail Price'];

        foreach ($products as $product) {
            if ($product->hasVariants() && $product->variant) {
                // Product has variants
                $variantPrices = $product->prices()->where('variant_id', '!=', null)->get();

                foreach ($variantPrices as $price) {
                    $productNameWithVariant = $product->name . ' - ' . ($product->variant->variant_name ?? '') . ': ' . ($price->variant_value ?? '');
                    $csvData[] = [
                        $price->id,
                        $product->code,
                        $productNameWithVariant,
                        $price->wholesale_price ?? 0,
                        $price->distributor_price ?? 0,
                        $price->retail_price ?? 0,
                    ];
                }
            } else {
                // Product has no variants
                $price = $product->prices()->where('variant_id', null)->first();
                if ($price) {
                    $csvData[] = [
                        $price->id,
                        $product->code,
                        $product->name,
                        $price->wholesale_price ?? 0,
                        $price->distributor_price ?? 0,
                        $price->retail_price ?? 0,
                    ];
                }
            }
        }

        $filename = 'product-prices-' . date('Y-m-d-His') . '.csv';
        $handle = fopen('php://memory', 'w');

        // Add UTF-8 BOM so Excel opens it correctly
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response()->streamDownload(
            function () use ($csv) {
                echo $csv;
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    /**
     * Handle file upload for import
     */
    public function handleFileUpload()
    {
        $this->validate();

        try {
            $this->importErrors = [];
            $this->importedData = [];

            $file = $this->uploadedFile;
            $extension = $file->getClientOriginalExtension();

            if ($extension === 'csv') {
                $this->importedData = $this->parseCSVFile($file);
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                $this->importedData = $this->parseExcelFile($file);
            }

            if (empty($this->importErrors)) {
                $this->showImportPreview = true;
                $this->dispatch('show-import-preview');
            }
        } catch (Exception $e) {
            $this->importErrors[] = 'File parsing error: ' . $e->getMessage();
        }
    }

    /**
     * Parse CSV file with auto-detect delimiter
     */
    private function parseCSVFile($file)
    {
        $data = [];
        $filePath = $file->getRealPath();

        // Auto-detect delimiter
        $delimiter = ',';
        $fileHeader = file_get_contents($filePath, false, null, 0, 2048);
        if ($fileHeader !== false) {
            $semicolons = substr_count($fileHeader, ';');
            $commas = substr_count($fileHeader, ',');
            $tabs = substr_count($fileHeader, "\t");
            if ($semicolons > $commas && $semicolons > $tabs) {
                $delimiter = ';';
            } elseif ($tabs > $commas && $tabs > $semicolons) {
                $delimiter = "\t";
            }
        }

        $handle = fopen($filePath, 'r');
        $headers = null;
        $rowNumber = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            if ($rowNumber === 1) {
                $headers = $row;
                continue;
            }

            if (empty(array_filter($row))) {
                continue; // Skip empty rows
            }

            $data[] = $this->processRow($row, $headers, $rowNumber);
        }

        fclose($handle);
        return $data;
    }

    /**
     * Parse Excel file
     */
    private function parseExcelFile($file)
    {
        $data = [];
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            foreach ($rows as $rowNumber => $row) {
                if ($rowNumber === 0) {
                    continue; // Skip header row
                }

                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }

                $data[] = $this->processRow($row, $rows[0] ?? [], $rowNumber + 1);
            }
        } catch (Exception $e) {
            $this->importErrors[] = 'Excel parsing error: ' . $e->getMessage();
        }

        return $data;
    }

    /**
     * Helper to normalize encoding corruptions (e.g. from Excel reading UTF-8 as ANSI)
     */
    private function normalizeEncodingCorruptions($str)
    {
        $corruptions = [
            'Ï†' => 'φ',
            'Î†' => 'φ',
            'Î¦' => 'Φ',
            'Ï' => 'φ',
            'Î' => 'φ',
            'Ã¸' => 'ø',
            'Ã˜' => 'Ø',
        ];
        return str_replace(array_keys($corruptions), array_values($corruptions), $str);
    }

    /**
     * Helper to normalize quotes
     */
    private function normalizeQuotes($str)
    {
        $smartQuotes = [
            '“' => '"',
            '”' => '"',
            '‘' => "'",
            '’' => "'",
            '„' => '"',
            '‹' => "'",
            '›' => "'",
        ];
        return str_replace(array_keys($smartQuotes), array_values($smartQuotes), $str);
    }

    /**
     * Helper to clean string input
     */
    private function cleanString($str)
    {
        if ($str === null || $str === '') {
            return '';
        }
        $str = preg_replace('/^\xEF\xBB\xBF/', '', $str); // Remove BOM
        $str = $this->normalizeEncodingCorruptions($str); // Fix encoding corruptions
        $str = $this->normalizeQuotes($str);
        $str = preg_replace('/\s+/u', ' ', $str); // Normalize all spaces (including non-breaking spaces)
        return trim($str);
    }

    /**
     * Helper to clean price strings (handling thousands separators, currency symbols, spaces)
     */
    private function cleanPrice($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        $str = str_replace(',', '', (string) $value);
        if (preg_match('/-?\d+(?:\.\d+)?/', $str, $matches)) {
            return (float) $matches[0];
        }
        return 0.0;
    }

    /**
     * Process a single row and validate/map data
     */
    private function processRow($row, $headers, $rowNumber)
    {
        // Default column indices (assuming no headers match)
        $priceIdIndex = -1;
        $productCodeIndex = 0;
        $productNameVariantIndex = 1;
        $wholesalePriceIndex = 2;
        $distributorPriceIndex = 3;
        $retailPriceIndex = 4;

        // Map column indices dynamically based on headers
        if (!empty($headers)) {
            foreach ($headers as $index => $header) {
                $headerClean = mb_strtolower($this->cleanString($header));
                if (str_contains($headerClean, 'price id') || str_contains($headerClean, 'price_id')) {
                    $priceIdIndex = $index;
                } elseif (str_contains($headerClean, 'product code') || str_contains($headerClean, 'code')) {
                    $productCodeIndex = $index;
                } elseif (str_contains($headerClean, 'product name') || str_contains($headerClean, 'name')) {
                    $productNameVariantIndex = $index;
                } elseif (str_contains($headerClean, 'wholesale')) {
                    $wholesalePriceIndex = $index;
                } elseif (str_contains($headerClean, 'distributor')) {
                    $distributorPriceIndex = $index;
                } elseif (str_contains($headerClean, 'retail')) {
                    $retailPriceIndex = $index;
                }
            }
        }

        $priceIdVal = null;
        if ($priceIdIndex !== -1) {
            $priceIdVal = $this->cleanString($row[$priceIdIndex] ?? '');
        }

        $productCode = $this->cleanString($row[$productCodeIndex] ?? '');
        $productNameVariant = $this->cleanString($row[$productNameVariantIndex] ?? '');
        $wholesalePrice = $this->cleanPrice($row[$wholesalePriceIndex] ?? 0);
        $distributorPrice = $this->cleanPrice($row[$distributorPriceIndex] ?? 0);
        $retailPrice = $this->cleanPrice($row[$retailPriceIndex] ?? 0);

        $result = [
            'product_code' => $productCode,
            'product_name_variant' => $productNameVariant,
            'wholesale_price' => $wholesalePrice,
            'distributor_price' => $distributorPrice,
            'retail_price' => $retailPrice,
            'row_number' => $rowNumber,
            'status' => 'pending',
            'error' => null,
            'price_id' => null,
            'product_id' => null,
        ];

        // 1. Check if we can map directly by Price ID
        if ($priceIdVal !== '' && is_numeric($priceIdVal)) {
            $priceId = (int) $priceIdVal;
            $price = ProductPrice::with(['product', 'variant'])->find($priceId);

            if ($price) {
                $result['price_id'] = $price->id;
                $result['product_id'] = $price->product_id;
                // Update product_code and product_name_variant to match database value for cleaner preview
                if ($price->product) {
                    $result['product_code'] = $price->product->code;
                    if ($price->isVariantBased()) {
                        $result['product_name_variant'] = $price->product->name . ' - ' . ($price->variant->variant_name ?? 'Variant') . ': ' . $price->variant_value;
                    } else {
                        $result['product_name_variant'] = $price->product->name;
                    }
                }
                $result['status'] = 'ready';
                return $result;
            } else {
                $result['error'] = 'Price ID not found: ' . $priceId;
                $result['status'] = 'error';
                return $result;
            }
        }

        // 2. Fallback matching logic by Code and Name
        if ($productCode === '') {
            $result['error'] = 'Product code is required';
            $result['status'] = 'error';
            return $result;
        }

        // Normalize product code if it looks like a float but is integer code (e.g. 123.0 -> 123)
        if (is_numeric($productCode) && str_contains($productCode, '.')) {
            $parts = explode('.', $productCode);
            if (count($parts) === 2 && (int) $parts[1] === 0) {
                $productCode = $parts[0];
            }
        }

        // Find active product detail
        $product = ProductDetail::whereRaw('LOWER(TRIM(code)) = ?', [mb_strtolower($productCode)])
            ->where('status', 'active')
            ->first();

        // Excel often strips leading zeros (e.g. 000 -> 0), so match numeric codes by numeric value.
        if (!$product && preg_match('/^\d+$/', $productCode)) {
            $product = ProductDetail::where('status', 'active')
                ->whereRaw('TRIM(code) REGEXP "^[0-9]+$"')
                ->whereRaw('CAST(TRIM(code) AS UNSIGNED) = ?', [(int) $productCode])
                ->first();
        }

        // Fallback lookup by product name
        if (!$product) {
            $namePart = $productNameVariant;
            $hasVariantInName = str_contains($namePart, ':');
            if ($hasVariantInName) {
                $namePart = trim((string) explode(':', $namePart)[0]);
                if (str_contains($namePart, ' - ')) {
                    $parts = explode(' - ', $namePart);
                    if (count($parts) > 1) {
                        array_pop($parts); // Remove the last part which is the variant name (e.g., SIZE)
                        $namePart = implode(' - ', $parts);
                    }
                }
            }

            $namePart = trim($namePart);
            if ($namePart !== '') {
                $product = ProductDetail::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($namePart)])
                    ->where('status', 'active')
                    ->first();
            }
        }

        if (!$product) {
            $result['error'] = 'Active product code or name not found: ' . $productCode;
            $result['status'] = 'error';
            return $result;
        }

        $result['product_id'] = $product->id;

        // Find the corresponding price record
        if ($product->hasVariants()) {
            // Extract variant value from product name if it contains variant info
            $variantValue = $this->extractVariantValue($productNameVariant, $product);
            if ($variantValue !== null && $variantValue !== '') {
                $price = $product->prices()
                    ->where('variant_id', $product->variant_id)
                    ->whereRaw('LOWER(TRIM(variant_value)) = ?', [mb_strtolower(trim($variantValue))])
                    ->first();

                if (!$price) {
                    $result['error'] = 'Variant not found for this product: ' . $variantValue;
                    $result['status'] = 'error';
                    return $result;
                }
                $result['price_id'] = $price->id;
            } else {
                $result['error'] = 'Could not parse variant value from: ' . $productNameVariant;
                $result['status'] = 'error';
                return $result;
            }
        } else {
            // Single price product
            $price = $product->prices()->where('variant_id', null)->first();
            if (!$price) {
                $result['error'] = 'Price record not found for product';
                $result['status'] = 'error';
                return $result;
            }
            $result['price_id'] = $price->id;
        }

        $result['status'] = 'ready';
        return $result;
    }

    /**
     * Extract variant value from product name string
     */
    private function extractVariantValue($productNameVariant, $product)
    {
        // Format is typically "Product Name - VariantName: VariantValue"
        $parts = explode(':', $productNameVariant);
        if (count($parts) >= 2) {
            return trim((string) end($parts));
        }
        return null;
    }

    /**
     * Apply imported prices
     */
    public function applyImportedPrices()
    {
        if (empty($this->importedData)) {
            $this->importErrors[] = 'No data to import';
            $this->dispatch('close-import-modal');
            return;
        }

        $successCount = 0;
        $failureCount = 0;
        $this->importErrors = [];

        foreach ($this->importedData as $item) {
            if ($item['status'] === 'error') {
                $failureCount++;
                continue;
            }

            if (!$item['price_id']) {
                $failureCount++;
                continue;
            }

            try {
                $price = ProductPrice::find($item['price_id']);
                if ($price) {
                    $price->update([
                        'wholesale_price' => $item['wholesale_price'],
                        'distributor_price' => $item['distributor_price'],
                        'retail_price' => $item['retail_price'],
                    ]);
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (Exception $e) {
                $failureCount++;
                $this->importErrors[] = 'Error updating price ID ' . $item['price_id'] . ': ' . $e->getMessage();
            }
        }

        $this->showImportPreview = false;
        $this->importSuccess = true;
        $this->importedData = [];
        $this->uploadedFile = null;

        // Show success message
        $this->showToast('success', "Successfully imported $successCount prices" . ($failureCount > 0 ? " with $failureCount failures" : ''));

        $this->dispatch('prices-imported');
        $this->dispatch('close-import-modal');
    }

    /**
     * Cancel import preview
     */
    public function cancelImport()
    {
        $this->showImportPreview = false;
        $this->importedData = [];
        $this->uploadedFile = null;
    }

    /**
     * Update individual product price
     */
    public function updatePrice($priceId, $field, $value)
    {
        try {
            $price = ProductPrice::find($priceId);
            if ($price) {
                $price->update([
                    $field => (float) $value,
                ]);
                $this->showToast('success', 'Price updated successfully');
            }
        } catch (Exception $e) {
            $this->addError($field, 'Error updating price: ' . $e->getMessage());
        }
    }

    /**
     * Search products
     */
    public function search()
    {
        $this->currentPage = 1;
    }

    public function updatedPerPage()
    {
        $this->currentPage = 1;
    }

    public function updatedSearchTerm()
    {
        $this->currentPage = 1;
    }

    /**
     * Go to page
     */
    public function gotoPage($page)
    {
        $this->currentPage = $page;
    }

    public function previousPage()
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function nextPage()
    {
        $totalItems = $this->getProductsPriceData()->total();
        $lastPage = (int) ceil($totalItems / max(1, (int) $this->perPage));

        if ($this->currentPage < $lastPage) {
            $this->currentPage++;
        }
    }

    /**
     * Show toast notification (elegant alert alternative)
     * 
     * @param string $type - 'success', 'error', 'warning', 'info'
     * @param string $message - The message to display
     */
    private function showToast($type, $message)
    {
        $bgColors = [
            'success' => '#10b981',
            'error' => '#ef4444',
            'warning' => '#f59e0b',
            'info' => '#3b82f6',
        ];

        $icons = [
            'success' => '✓',
            'error' => '✕',
            'warning' => '⚠',
            'info' => 'ℹ',
        ];

        $bg = $bgColors[$type] ?? $bgColors['info'];
        $icon = $icons[$type] ?? $icons['info'];

        $escapedMessage = addslashes($message);

        $this->js("
            const toast = document.createElement('div');
            toast.style.cssText = 'position:fixed;top:20px;right:20px;background:{$bg};color:white;padding:16px 24px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:9999;font-size:14px;font-weight:600;display:flex;align-items:center;gap:12px;animation:slideIn 0.3s ease;min-width:300px;max-width:500px;';
            toast.innerHTML = '<span style=\"font-size:20px;font-weight:bold;\">{$icon}</span><span>{$escapedMessage}</span>';
            document.body.appendChild(toast);
            
            const style = document.createElement('style');
            style.textContent = '@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } } @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(400px); opacity: 0; } }';
            document.head.appendChild(style);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        ");
    }
}
