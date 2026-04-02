<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// APP_URL=http://192.168.1.18:8000
// API Controllers
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ChequeController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\StaffManagementController;
use App\Http\Controllers\Api\BusinessSettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ============================================================================
// AUTHENTICATION ROUTES
// ============================================================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Business Settings
Route::get('/business-settings', [BusinessSettingController::class, 'index']);
Route::post('/business-settings', [BusinessSettingController::class, 'update']);

// Protected auth routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'changePassword']);
});

// Authenticated user route
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});

// ============================================================================
// PUBLIC API ROUTES (For Mobile App)
// ============================================================================

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/recent-activity', [DashboardController::class, 'recentActivity']);

// Products
Route::apiResource('products', ProductController::class);

// Categories
Route::apiResource('categories', CategoryController::class);

// Brands
Route::apiResource('brands', BrandController::class);

// Suppliers  
Route::apiResource('suppliers', SupplierController::class);

// Customers
Route::apiResource('customers', CustomerController::class);

// Sales
Route::apiResource('sales', SaleController::class);
Route::get('/sales-due', [SaleController::class, 'dueSales']);
Route::post('/sales/{id}/payment', [SaleController::class, 'recordPayment']);
Route::get('/customers/{id}/due-summary', [SaleController::class, 'customerDueSummary']);

// Quotations
Route::apiResource('quotations', QuotationController::class);
Route::post('/quotations/{id}/convert-to-sale', [QuotationController::class, 'convertToSale']);

// Purchase Orders
Route::apiResource('purchase-orders', PurchaseOrderController::class);
Route::post('/purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive']);

// GRNs (alias for purchase-orders)
Route::get('/grns', [PurchaseOrderController::class, 'index']);
Route::get('/grns/{id}', [PurchaseOrderController::class, 'show']);
Route::post('/grns', [PurchaseOrderController::class, 'storeGRN']);

// Reports
Route::get('/reports/day-summary', [ReportController::class, 'daySummary']);
Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
Route::get('/reports/analytics', [ReportController::class, 'analytics']);

// Additional endpoints for compatibility
Route::get('/product-prices', function () {
    return response()->json(['results' => []]);
});

Route::get('/product-stocks', function () {
    return response()->json(['results' => []]);
});

// Payments
Route::apiResource('payments', PaymentController::class);

// Cheques
Route::get('/cheques', [ChequeController::class, 'index']);
Route::get('/cheques/{id}', [ChequeController::class, 'show']);

// Expenses
Route::apiResource('expenses', ExpenseController::class);
Route::get('/expenses-stats', [ExpenseController::class, 'stats']);

// Returns
use App\Http\Controllers\Api\ReturnController;
Route::get('/returns/customer', [ReturnController::class, 'getCustomerReturns']);
Route::post('/returns/customer', [ReturnController::class, 'storeCustomerReturn']);
Route::get('/returns/supplier', [ReturnController::class, 'getSupplierReturns']);
Route::post('/returns/supplier', [ReturnController::class, 'storeSupplierReturn']);

Route::get('/cash-in-hand', function () {
    return response()->json(['results' => []]);
});

Route::get('/deposits', function () {
    return response()->json(['results' => []]);
});

// Settings
Route::apiResource('settings', SettingsController::class);
Route::get('/settings-staff', [SettingsController::class, 'getStaff']);
Route::post('/settings-staff', [SettingsController::class, 'storeStaff']);
Route::put('/settings-staff/{id}', [SettingsController::class, 'updateStaff']);
Route::delete('/settings-staff/{id}', [SettingsController::class, 'destroyStaff']);
Route::put('/settings-staff/{id}/permissions', [SettingsController::class, 'updateStaffPermissions']);
Route::get('/settings-permissions', [SettingsController::class, 'getAvailablePermissions']);
Route::get('/expense-categories', [SettingsController::class, 'getExpenseCategories']);
Route::post('/expense-categories', [SettingsController::class, 'storeExpenseCategory']);
Route::delete('/expense-categories/{id}', [SettingsController::class, 'destroyExpenseCategory']);

// Staff Management (Sales Reps)
Route::get('/staff-sales-summary', [StaffManagementController::class, 'getSalesSummary']);
Route::get('/staff-sales/{staffId}', [StaffManagementController::class, 'getStaffSaleDetails']);
Route::get('/staff-stock-details', [StaffManagementController::class, 'getStockDetails']);
Route::get('/staff-stock/{staffId}/products', [StaffManagementController::class, 'getStaffStockProducts']);
Route::get('/staff-due-details', [StaffManagementController::class, 'getDueDetails']);

// Payment Approval
Route::get('/payments-approval', [StaffManagementController::class, 'getPaymentsForApproval']);
Route::post('/payments/{id}/approve', [StaffManagementController::class, 'approvePayment']);
Route::post('/payments/{id}/reject', [StaffManagementController::class, 'rejectPayment']);

// Stock Allocation
Route::get('/products-for-allocation', [StaffManagementController::class, 'getProductsForAllocation']);
Route::post('/allocate-products', [StaffManagementController::class, 'allocateProducts']);

// Staff Expenses
Route::get('/staff-expenses', [StaffManagementController::class, 'getStaffExpenses']);
Route::get('/staff-expenses/{staffId}', [StaffManagementController::class, 'getStaffExpenseDetails']);

// Staff Live Locations (Admin)
Route::get('/admin/reps/live', [StaffManagementController::class, 'getLiveStaffLocations']);

// ============================================================================
// NOTIFICATIONS & PUSH TOKENS
// ============================================================================
use App\Http\Controllers\Api\NotificationController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Push tokens
    Route::post('/push-tokens', function (Request $request) {
        // Store push token (best-effort, no-op if not implemented)
        return response()->json(['success' => true, 'message' => 'Token registered']);
    });
});

// ============================================================================
// STAFF APP ROUTES (For Mobile App - Staff Users)
// ============================================================================
use App\Http\Controllers\Api\StaffAppController;

Route::middleware('auth:sanctum')->prefix('staff-app')->group(function () {
    // Dashboard
    Route::get('/dashboard', [StaffAppController::class, 'getDashboard']);

    // Permissions
    Route::get('/permissions', [StaffAppController::class, 'getPermissions']);

    // Stock (Read-only)
    Route::get('/my-stock', [StaffAppController::class, 'getMyStock']);

    // Sales
    Route::get('/my-sales', [StaffAppController::class, 'getMySales']);
    Route::get('/my-sales/{id}', [StaffAppController::class, 'getSaleDetails']);
    Route::post('/create-sale', [StaffAppController::class, 'createSale']);

    // Customers
    Route::get('/my-customers', [StaffAppController::class, 'getMyCustomers']);
    Route::post('/create-customer', [StaffAppController::class, 'createCustomer']);

    // Products for sale
    Route::get('/products-for-sale', [StaffAppController::class, 'getProductsForSale']);

    // Payments
    Route::get('/my-payments', [StaffAppController::class, 'getMyPayments']);
    Route::get('/due-sales', [StaffAppController::class, 'getDueSales']);
    Route::post('/sales/{id}/payment', [StaffAppController::class, 'addPayment']);

    // Returns (Read-only)
    Route::get('/my-returns', [StaffAppController::class, 'getMyReturns']);

    // Payment Collection
    Route::get('/due-customers', [StaffAppController::class, 'getDueCustomers']);
    Route::get('/due-customers/{id}/bills', [StaffAppController::class, 'getCustomerDueBills']);
    Route::post('/collect-payment', [StaffAppController::class, 'collectPayment']);

    // Sale Approval (Admin only)
    Route::get('/pending-sales', [StaffAppController::class, 'getPendingSales']);
    Route::post('/approve-sale/{id}', [StaffAppController::class, 'approveSale']);
    Route::post('/reject-sale/{id}', [StaffAppController::class, 'rejectSale']);

    // Location tracking
    Route::post('/location', [StaffAppController::class, 'postLocation']);
});

