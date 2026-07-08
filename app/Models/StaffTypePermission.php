<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StaffTypePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_type',
        'permission_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Available staff types
     */
    public static function staffTypes()
    {
        return [
            'salesman' => 'Salesman',
            'delivery_man' => 'Delivery Man',
            'shop_staff' => 'Shop Staff',
        ];
    }

    /**
     * Available permission keys for staff types and their descriptions
     */
    public static function availablePermissions()
    {
        return [
            // Dashboard
            'view_dashboard' => 'View Dashboard',

            // Sales
            'view_sales' => 'View Sales',
            'create_sales' => 'Create Sales',
            'edit_pending_sales' => 'Edit Pending Sales (Before Approval)',
            'view_own_sales_only' => 'View Only Own Sales',

            // Returns
            'create_returns' => 'Create Returns',
            'view_returns' => 'View Returns',

            // Customers
            'view_customers' => 'View Customers',
            'view_customer_dues' => 'View Customer Dues',

            // Payments
            'collect_payments' => 'Collect Payments',
            'view_payments' => 'View Payments',

            // Deliveries
            'view_pending_deliveries' => 'View Pending Deliveries',
            'view_completed_deliveries' => 'View Completed Deliveries',
            'confirm_deliveries' => 'Confirm Deliveries',

            // Products
            'view_products' => 'View Products',
            'view_cost_price' => 'View Cost Price',
            'view_wholesale_price' => 'View Wholesale Price',
            'view_retail_price' => 'View Retail Price',
            'view_distributor_price' => 'View Distributor Price',

            // Expenses
            'add_expenses' => 'Add Expenses',
            'view_expenses' => 'View Expenses',

            // Quotations
            'view_quotations' => 'View Quotations',
            'create_quotations' => 'Create Quotations',
        ];
    }

    /**
     * Permission categories for organized display
     */
    public static function permissionCategories()
    {
        return [
            'Dashboard' => [
                'view_dashboard',
            ],
            'Sales Management' => [
                'view_sales',
                'create_sales',
                'edit_pending_sales',
                'view_own_sales_only',
            ],
            'Return Management' => [
                'create_returns',
                'view_returns',
            ],
            'Customer Management' => [
                'view_customers',
                'view_customer_dues',
            ],
            'Payment Management' => [
                'collect_payments',
                'view_payments',
            ],
            'Delivery Management' => [
                'view_pending_deliveries',
                'view_completed_deliveries',
                'confirm_deliveries',
            ],
            'Product Management' => [
                'view_products',
                'view_cost_price',
                'view_wholesale_price',
                'view_retail_price',
                'view_distributor_price',
            ],
            'Expense Management' => [
                'add_expenses',
                'view_expenses',
            ],
            'Quotation Management' => [
                'view_quotations',
                'create_quotations',
            ],
        ];
    }

    /**
     * Default permissions for each staff type
     */
    public static function defaultPermissions()
    {
        return [
            'salesman' => [
                'view_dashboard',
                'view_sales',
                'create_sales',
                'edit_pending_sales',
                'view_own_sales_only',
                'create_returns',
                'view_returns',
                'view_customers',
                'view_customer_dues',
                'view_products',
                'view_distributor_price',
                'add_expenses',
                'view_quotations',
                'create_quotations',
            ],
            'delivery_man' => [
                'view_dashboard',
                'view_sales',
                'view_pending_deliveries',
                'view_completed_deliveries',
                'confirm_deliveries',
                'view_customer_dues',
                'collect_payments',
                'view_payments',
                'view_products',
                'view_distributor_price',
                'add_expenses',
                'create_returns',
                'view_returns',
            ],
            'shop_staff' => [
                'view_dashboard',
                'view_products',
                'view_wholesale_price',
                'view_retail_price',
            ],
        ];
    }

    /**
     * Check if a staff type has a specific permission
     */
    public static function hasPermission($staffType, $permissionKey)
    {
        $cacheKey = "staff_type_permission_{$staffType}_{$permissionKey}";

        return Cache::remember($cacheKey, 3600, function () use ($staffType, $permissionKey) {
            $permission = self::where('staff_type', $staffType)
                ->where('permission_key', $permissionKey)
                ->first();

            // If no explicit permission is set, use defaults
            if (!$permission) {
                $defaults = self::defaultPermissions()[$staffType] ?? [];
                return in_array($permissionKey, $defaults);
            }

            return $permission->is_active;
        });
    }

    /**
     * Get all permissions for a staff type
     */
    public static function getPermissions($staffType)
    {
        $cacheKey = "staff_type_permissions_{$staffType}";

        return Cache::remember($cacheKey, 3600, function () use ($staffType) {
            $customPermissions = self::where('staff_type', $staffType)->get();

            // If no custom permissions are set, return defaults
            if ($customPermissions->isEmpty()) {
                return self::defaultPermissions()[$staffType] ?? [];
            }

            return $customPermissions->where('is_active', true)->pluck('permission_key')->toArray();
        });
    }

    /**
     * Sync permissions for a staff type
     */
    public static function syncPermissions($staffType, array $permissions)
    {
        // Delete existing permissions for this staff type
        self::where('staff_type', $staffType)->delete();

        // Create new permissions
        foreach ($permissions as $permission) {
            self::create([
                'staff_type' => $staffType,
                'permission_key' => $permission,
                'is_active' => true,
            ]);
        }

        // Clear cache for this staff type
        $availablePermissions = array_keys(self::availablePermissions());
        foreach ($availablePermissions as $permKey) {
            Cache::forget("staff_type_permission_{$staffType}_{$permKey}");
        }
        Cache::forget("staff_type_permissions_{$staffType}");
    }

    /**
     * Reset permissions for a staff type to defaults
     */
    public static function resetToDefaults($staffType)
    {
        self::where('staff_type', $staffType)->delete();

        // Clear cache
        $availablePermissions = array_keys(self::availablePermissions());
        foreach ($availablePermissions as $permKey) {
            Cache::forget("staff_type_permission_{$staffType}_{$permKey}");
        }
        Cache::forget("staff_type_permissions_{$staffType}");
    }
}
