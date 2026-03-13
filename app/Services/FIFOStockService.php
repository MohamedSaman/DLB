<?php

namespace App\Services;

use App\Models\ProductBatch;
use App\Models\ProductStock;
use App\Models\ProductPrice;
use App\Models\ProductDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FIFOStockService
{
    /**
     * Deduct stock from batches using FIFO method
     * Returns array with deduction details
     */
    public static function deductStock($productId, $quantity, $variantId = null, $variantValue = null)
    {
        $remainingQty = $quantity;
        $deductions = [];
        $totalCost = 0;

        // Load ProductStock once so batch/manual paths use the same source record.
        $stockQuery = ProductStock::where('product_id', $productId);
        if ($variantId) {
            $stockQuery->where('variant_id', $variantId);
            if ($variantValue) {
                $stockQuery->where('variant_value', $variantValue);
            }
        } else {
            $stockQuery->where(function ($q) {
                $q->whereNull('variant_id')->orWhere('variant_id', 0);
            });
        }
        $stock = $stockQuery->first();
        $stockAvailable = $stock ? (int) $stock->available_stock : 0;

        // Get active batches in FIFO order (oldest first) filtered by variant
        $batchQuery = ProductBatch::where('product_id', $productId)
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0);

        if ($variantId) {
            $batchQuery->where('variant_id', $variantId);
            if ($variantValue) {
                $batchQuery->where('variant_value', $variantValue);
            }
        } else {
            $batchQuery->where(function ($q) {
                $q->whereNull('variant_id')->orWhere('variant_id', 0);
            });
        }

        $batches = $batchQuery->orderBy('received_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Handle manually added stock without batches
        if ($batches->isEmpty()) {
            if (!$stock || $stock->available_stock < $quantity) {
                $available = $stock ? $stock->available_stock : 0;
                throw new \Exception("Insufficient stock. Required: {$quantity}, Available: {$available}");
            }

            // Manually added stock - deduct directly from ProductStock without batch tracking
            DB::beginTransaction();
            try {
                $stock->available_stock -= $quantity;
                $stock->sold_count += $quantity;
                $stock->updateTotals();

                DB::commit();
                return [
                    'success' => true,
                    'deductions' => [[
                        'batch_id' => null,
                        'batch_number' => 'Manual Stock',
                        'quantity' => $quantity,
                        'supplier_price' => 0,
                        'selling_price' => 0,
                        'cost' => 0,
                    ]],
                    'total_cost' => 0,
                    'average_cost' => 0,
                    'note' => 'Deducted from manually added stock (no batch records)',
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        // Check batch availability and allow mixed deduction using ProductStock when needed.
        $totalAvailable = $batches->sum('remaining_quantity');
        if ($stockAvailable < $quantity) {
            $available = max($stockAvailable, $totalAvailable);
            throw new \Exception("Insufficient stock. Required: {$quantity}, Available: {$available}");
        }

        $manualDeductionQty = 0;
        if ($totalAvailable < $quantity) {
            $manualDeductionQty = $quantity - $totalAvailable;
            $remainingQty = $totalAvailable;

            Log::warning('Mixed stock deduction used (batch + manual stock)', [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'variant_value' => $variantValue,
                'required_quantity' => $quantity,
                'batch_available' => $totalAvailable,
                'manual_quantity_used' => $manualDeductionQty,
                'product_stock_available' => $stockAvailable,
            ]);
        } else {
            $remainingQty = $quantity;
        }

        if ($totalAvailable <= 0 && $manualDeductionQty <= 0) {
            throw new \Exception("Insufficient stock. Required: {$quantity}, Available: {$totalAvailable}");
        }

        DB::beginTransaction();
        try {
            $batchDepleted = false;

            foreach ($batches as $batch) {
                if ($remainingQty <= 0) break;

                $deductQty = min($remainingQty, $batch->remaining_quantity);

                // Check if this batch will be depleted
                $willBeDepleted = ($deductQty == $batch->remaining_quantity);

                // Deduct from batch
                $batch->deduct($deductQty);

                if ($willBeDepleted) {
                    $batchDepleted = true;
                }

                // Track deduction details
                $deductions[] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'quantity' => $deductQty,
                    'supplier_price' => $batch->supplier_price,
                    'selling_price' => $batch->selling_price,
                    'cost' => $batch->supplier_price * $deductQty,
                ];

                $totalCost += $batch->supplier_price * $deductQty;
                $remainingQty -= $deductQty;
            }

            if ($manualDeductionQty > 0) {
                $deductions[] = [
                    'batch_id' => null,
                    'batch_number' => 'Manual Stock',
                    'quantity' => $manualDeductionQty,
                    'supplier_price' => 0,
                    'selling_price' => 0,
                    'cost' => 0,
                ];
            }

            if ($stock) {
                $stock->available_stock -= $quantity;
                $stock->sold_count += $quantity;
                $stock->updateTotals(); // Recalculate total_stock = available_stock + damage_stock
            }

            // If any batch was depleted, check and update main prices
            // This ensures prices reflect the current oldest active batch
            if ($batchDepleted) {
                self::updateMainPrices($productId, $variantId, $variantValue);
            }

            DB::commit();
            return [
                'success' => true,
                'deductions' => $deductions,
                'total_cost' => $totalCost,
                'average_cost' => $quantity > 0 ? $totalCost / $quantity : 0,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update main product prices when a batch is depleted
     * Uses the oldest active batch prices (next in FIFO queue)
     */
    public static function updateMainPrices($productId, $variantId = null, $variantValue = null)
    {
        // Get the next active batch (if any) filtered by variant
        $nextBatchQuery = ProductBatch::where('product_id', $productId)
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0);

        if ($variantId) {
            $nextBatchQuery->where('variant_id', $variantId);
            if ($variantValue) {
                $nextBatchQuery->where('variant_value', $variantValue);
            }
        } else {
            $nextBatchQuery->where(function ($q) {
                $q->whereNull('variant_id')->orWhere('variant_id', 0);
            });
        }

        $nextBatch = $nextBatchQuery->orderBy('received_date', 'asc')
            ->orderBy('id', 'asc')
            ->first();

        if ($nextBatch) {
            // Update prices based on variant or base product
            if ($variantId) {
                // Update variant-specific price
                $variantPriceQuery = ProductPrice::where('product_id', $productId)
                    ->where('variant_id', $variantId);
                if ($variantValue) {
                    $variantPriceQuery->where('variant_value', $variantValue);
                }
                $variantPrice = $variantPriceQuery->first();

                if ($variantPrice) {
                    $oldSupplierPrice = $variantPrice->supplier_price;
                    $oldWholesalePrice = $variantPrice->wholesale_price;
                    $oldRetailPrice = $variantPrice->retail_price;
                    $oldDistributorPrice = $variantPrice->distributor_price;

                    $variantPrice->supplier_price = $nextBatch->supplier_price;
                    $variantPrice->selling_price = $nextBatch->selling_price;
                    $variantPrice->wholesale_price = $nextBatch->wholesale_price ?? $oldWholesalePrice;
                    $variantPrice->retail_price = $nextBatch->retail_price ?? $oldRetailPrice;
                    $variantPrice->distributor_price = $nextBatch->distributor_price ?? $oldDistributorPrice;
                    $variantPrice->save();

                    Log::info("Product #{$productId} Variant #{$variantId} ({$variantValue}) prices updated", [
                        'old_supplier_price' => $oldSupplierPrice,
                        'new_supplier_price' => $nextBatch->supplier_price,
                        'batch_number' => $nextBatch->batch_number,
                    ]);
                } else {
                    // Create variant price if doesn't exist
                    ProductPrice::create([
                        'product_id' => $productId,
                        'variant_id' => $variantId,
                        'variant_value' => $variantValue,
                        'supplier_price' => $nextBatch->supplier_price,
                        'selling_price' => $nextBatch->selling_price,
                        'wholesale_price' => $nextBatch->wholesale_price ?? 0,
                        'retail_price' => $nextBatch->retail_price ?? 0,
                        'distributor_price' => $nextBatch->distributor_price ?? 0,
                        'discount_price' => 0,
                    ]);

                    Log::info("Product #{$productId} Variant #{$variantId} price record created", [
                        'supplier_price' => $nextBatch->supplier_price,
                        'batch_number' => $nextBatch->batch_number,
                    ]);
                }
            } else {
                // Update base product price
                $productPrice = ProductPrice::where('product_id', $productId)
                    ->where(function ($q) {
                        $q->whereNull('variant_id')->orWhere('variant_id', 0);
                    })->first();

                if ($productPrice) {
                    $oldSupplierPrice = $productPrice->supplier_price;
                    $oldSellingPrice = $productPrice->selling_price;

                    $productPrice->supplier_price = $nextBatch->supplier_price;
                    $productPrice->selling_price = $nextBatch->selling_price;
                    $productPrice->wholesale_price = $nextBatch->wholesale_price ?? $productPrice->wholesale_price;
                    $productPrice->retail_price = $nextBatch->retail_price ?? $productPrice->retail_price;
                    $productPrice->distributor_price = $nextBatch->distributor_price ?? $productPrice->distributor_price;
                    $productPrice->save();

                    Log::info("Product #{$productId} prices updated", [
                        'old_supplier_price' => $oldSupplierPrice,
                        'new_supplier_price' => $nextBatch->supplier_price,
                        'old_selling_price' => $oldSellingPrice,
                        'new_selling_price' => $nextBatch->selling_price,
                        'batch_number' => $nextBatch->batch_number,
                    ]);
                } else {
                    // Create price record if doesn't exist
                    ProductPrice::create([
                        'product_id' => $productId,
                        'supplier_price' => $nextBatch->supplier_price,
                        'selling_price' => $nextBatch->selling_price,
                        'wholesale_price' => $nextBatch->wholesale_price ?? 0,
                        'retail_price' => $nextBatch->retail_price ?? 0,
                        'distributor_price' => $nextBatch->distributor_price ?? 0,
                        'discount_price' => 0,
                    ]);

                    Log::info("Product #{$productId} price record created", [
                        'supplier_price' => $nextBatch->supplier_price,
                        'selling_price' => $nextBatch->selling_price,
                        'batch_number' => $nextBatch->batch_number,
                    ]);
                }
            }

            return true;
        }

        Log::info("No active batches found for Product #{$productId} to update prices");
        return false;
    }

    /**
     * Get current active batch prices for a product (with variant support)
     */
    public static function getCurrentBatchPrices($productId, $variantId = null, $variantValue = null)
    {
        $batchQuery = ProductBatch::where('product_id', $productId)
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0);

        if ($variantId) {
            $batchQuery->where('variant_id', $variantId);
            if ($variantValue) {
                $batchQuery->where('variant_value', $variantValue);
            }
        } else {
            $batchQuery->where(function ($q) {
                $q->whereNull('variant_id')->orWhere('variant_id', 0);
            });
        }

        $batch = $batchQuery->orderBy('received_date', 'asc')
            ->orderBy('id', 'asc')
            ->first();

        if ($batch) {
            return [
                'supplier_price' => $batch->supplier_price,
                'selling_price' => $batch->selling_price,
                'batch_number' => $batch->batch_number,
                'remaining_quantity' => $batch->remaining_quantity,
            ];
        }

        return null;
    }

    /**
     * Check available stock across all active batches (with variant support)
     */
    public static function getAvailableStock($productId, $variantId = null, $variantValue = null)
    {
        $query = ProductBatch::where('product_id', $productId)
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0);

        if ($variantId) {
            $query->where('variant_id', $variantId);
            if ($variantValue) {
                $query->where('variant_value', $variantValue);
            }
        } else {
            $query->where(function ($q) {
                $q->whereNull('variant_id')->orWhere('variant_id', 0);
            });
        }

        return $query->sum('remaining_quantity');
    }

    /**
     * Get batch details for a product (with variant support)
     */
    public static function getBatchDetails($productId, $variantId = null, $variantValue = null)
    {
        $query = ProductBatch::where('product_id', $productId)
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0);

        if ($variantId) {
            $query->where('variant_id', $variantId);
            if ($variantValue) {
                $query->where('variant_value', $variantValue);
            }
        } else {
            $query->where(function ($q) {
                $q->whereNull('variant_id')->orWhere('variant_id', 0);
            });
        }

        return $query->orderBy('received_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($batch) {
                return [
                    'batch_number' => $batch->batch_number,
                    'remaining_quantity' => $batch->remaining_quantity,
                    'supplier_price' => $batch->supplier_price,
                    'selling_price' => $batch->selling_price,
                    'wholesale_price' => $batch->wholesale_price ?? 0,
                    'retail_price' => $batch->retail_price ?? 0,
                    'distributor_price' => $batch->distributor_price ?? 0,
                    'received_date' => $batch->received_date->format('Y-m-d'),
                ];
            });
    }

    /**
     * Get price breakdown by batches for a given quantity
     * Returns array of [quantity, price] pairs showing how stock would be distributed
     */
    public static function getBatchPriceBreakdown($productId, $quantity, $priceType = 'distributor_price', $variantId = null, $variantValue = null)
    {
        $batches = self::getBatchDetails($productId, $variantId, $variantValue);

        if ($batches->isEmpty()) {
            // No batches found - check if manual stock exists
            $stockQuery = ProductStock::where('product_id', $productId);
            if ($variantId) {
                $stockQuery->where('variant_id', $variantId);
                if ($variantValue) {
                    $stockQuery->where('variant_value', $variantValue);
                }
            } else {
                $stockQuery->where(function ($q) {
                    $q->whereNull('variant_id')->orWhere('variant_id', 0);
                });
            }
            $stock = $stockQuery->first();

            // If stock exists, get price from product_prices table
            if ($stock && $stock->available_stock > 0) {
                $priceQuery = ProductPrice::where('product_id', $productId);
                if ($variantId) {
                    $priceQuery->where('variant_id', $variantId);
                    if ($variantValue) {
                        $priceQuery->where('variant_value', $variantValue);
                    }
                } else {
                    $priceQuery->where(function ($q) {
                        $q->whereNull('variant_id')->orWhere('variant_id', 0);
                    });
                }
                $priceRecord = $priceQuery->first();

                if ($priceRecord) {
                    return [[
                        'quantity' => min($quantity, $stock->available_stock),
                        'price' => $priceRecord->{$priceType} ?? 0,
                        'batch_number' => 'Manual Stock',
                    ]];
                }
            }

            return [];
        }

        $breakdown = [];
        $remainingQty = $quantity;

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) break;

            $takeQty = min($remainingQty, $batch['remaining_quantity']);
            $price = $batch[$priceType] ?? 0;

            // Check if we can merge with previous entry (same price)
            if (!empty($breakdown) && end($breakdown)['price'] == $price) {
                $breakdown[count($breakdown) - 1]['quantity'] += $takeQty;
            } else {
                $breakdown[] = [
                    'quantity' => $takeQty,
                    'price' => $price,
                    'batch_number' => $batch['batch_number'],
                ];
            }

            $remainingQty -= $takeQty;
        }

        return $breakdown;
    }
}
