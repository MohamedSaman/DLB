<?php

use App\Http\Controllers\Admin\CashController;
use App\Http\Controllers\Admin\PrintController;
use App\Http\Controllers\ProductApiController;
use Illuminate\Http\Request;
use App\Livewire\CustomLogin;
use App\Livewire\Admin\Products;
use App\Livewire\Staff\Billing;
use App\Livewire\Admin\MadeByList;
use App\Livewire\Admin\ProductTypes;
use App\Livewire\Admin\BillingPage;
use App\Livewire\Admin\ManageAdmin;
use App\Livewire\Admin\ManageStaff;
use App\Livewire\Staff\DuePayments;
use App\Livewire\Admin\SupplierList;
use App\Livewire\Admin\ViewPayments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\AdminDashboard;
use App\Livewire\Admin\ManageCustomer;
use App\Livewire\Admin\ProductBrandlist;
use App\Livewire\Staff\StaffDashboard;
use App\Livewire\Admin\StaffDueDetails;
use App\Livewire\Admin\PaymentApprovals;
use App\Livewire\Admin\StaffSaleDetails;
use App\Livewire\Admin\StaffStockDetails;
use App\Livewire\Admin\ProductCategorylist;
use App\Livewire\Admin\ProductStockDetails;
use App\Livewire\Admin\ProductDialColorlist;
use App\Livewire\Admin\ProductGlassTypeList;
use App\Livewire\Admin\ProductStrapMaterial;
use App\Livewire\Staff\StaffStockOverview;
use App\Http\Controllers\ReceiptController;
use App\Livewire\Admin\CustomerSaleDetails;
use App\Livewire\Admin\ProductStrapColorlist;
use App\Livewire\Staff\CustomerSaleManagement;
use App\Livewire\Admin\StoreBilling;
use App\Livewire\Admin\DuePayments as AdminDuePayments;
use App\Livewire\Admin\StaffStockDetails as StaffStockDetailsExport;
use App\Livewire\Staff\StoreBilling as StaffStoreBilling;
use App\Http\Controllers\ProductsExportController;
use App\Http\Controllers\StaffSaleExportController;
use App\Livewire\Admin\GRN;
use App\Livewire\Admin\StaffAttendance;
use App\Livewire\Admin\StaffSallary;
use App\Livewire\Admin\StaffSalary;
use App\Livewire\Admin\LoanManage;
use App\Livewire\Admin\Quotation;
use App\Livewire\Admin\SalesApproval;
use App\Livewire\Admin\SupplierManage;
use App\Livewire\Admin\Reports;
use App\Livewire\Admin\Analytics;
use App\Livewire\Admin\QuotationSystem;
use App\Livewire\Admin\QuotationList;
use App\Livewire\Admin\SalesSystem;
use App\Livewire\Admin\SalesList;
use App\Livewire\Admin\PosSales;
use App\Livewire\Admin\PurchaseOrderList;
use App\Livewire\Admin\StaffProductAllocation;
use App\Livewire\Admin\StaffAllocatedList;
use App\Livewire\Admin\ViewStaffAllocatedProducts;
use App\Livewire\Admin\StaffProductReentry;
use App\Models\Setting as ModelsSetting;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\Expenses;
use App\Livewire\Admin\Income;
use App\Livewire\Admin\ReturnList;
use App\Livewire\Admin\ReturnProduct;
use App\Livewire\Admin\AddCustomerReceipt;
use App\Livewire\Admin\AddSupplierReceipt;
use App\Livewire\Admin\ChequeList;
use App\Livewire\Admin\DaySummary;
use App\Livewire\Admin\DaySummaryDetails;
use App\Livewire\Admin\Deposits;
use App\Livewire\Admin\ListCustomerReceipt;
use App\Livewire\Admin\ListSupplierReceipt;
use App\Livewire\Admin\ReturnCheque;

use App\Livewire\Admin\ReturnSupplier;
use App\Livewire\Admin\ListSupplierReturn;
use App\Livewire\Admin\ProfitLoss;
use App\Livewire\Admin\StaffSalesView;
use App\Livewire\Admin\StaffPaymentApproval;

// Staff Type Components
use App\Livewire\Admin\SaleApproval;
use App\Livewire\Admin\PaymentApproval;
use App\Livewire\Salesman\SalesmanDashboard;
use App\Livewire\Salesman\SalesmanBilling;
use App\Livewire\Salesman\SalesmanProductList;
use App\Livewire\Salesman\SalesmanSalesList;
use App\Livewire\Salesman\SalesmanCustomerDues;
use App\Livewire\DeliveryMan\DeliveryManDashboard;
use App\Livewire\DeliveryMan\DeliveryManPendingDeliveries;
use App\Livewire\DeliveryMan\DeliveryManCompletedDeliveries;
use App\Livewire\DeliveryMan\DeliveryManPaymentCollection;
use App\Livewire\DeliveryMan\DeliveryManPaymentList;
use App\Livewire\DeliveryMan\DeliveryManReturnProduct;
use App\Livewire\DeliveryMan\DeliveryManReturnList;
use App\Livewire\ShopStaff\ShopStaffDashboard;
use App\Livewire\ShopStaff\ShopStaffProductList;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public routes
Route::get('/', CustomLogin::class)->name('welcome')->middleware('guest');

// Custom logout route
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// Routes that require authentication
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

    // Profile route - accessible to all authenticated users
    Route::get('/user/profile', function () {
        return view('profile.show');
    })->name('profile.show');

    // Generic dashboard route: redirect authenticated users to their role-specific dashboard
    Route::get('/dashboard', function () {
        $user = Auth::user();

        // If for some reason there's no authenticated user, send them to the welcome page
        if (!$user) {
            return redirect()->route('welcome');
        }

        // Admin users go to admin dashboard
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        // Staff users - redirect based on staff_type
        if ($user->isStaff()) {
            // Check staff type and redirect accordingly
            switch ($user->staff_type) {
                case 'salesman':
                    return redirect()->route('salesman.dashboard');
                case 'delivery_man':
                    return redirect()->route('delivery.dashboard');
                case 'shop_staff':
                    return redirect()->route('shop-staff.dashboard');
                default:
                    // If no staff_type set, use the general staff dashboard
                    return redirect()->route('staff.dashboard');
            }
        }

        // Fallback: redirect to a safe page (profile) instead of looping back to dashboard
        return redirect()->route('profile.show');
    })->name('dashboard');

    // Settings route - accessible to all authenticated users

    // API route for products (client-side caching)
    Route::get('/api/products/all', [ProductApiController::class, 'getAllProducts'])->name('api.products.all');

    // !! Admin routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
        Route::get('/Product-list', Products::class)->name('Productes');
        Route::get('/manage-variants', \App\Livewire\Admin\ProductVariants::class)->name('manage-variants');
        Route::get('/update-price', \App\Livewire\Admin\UpdatePrice::class)->name('update-price');
        Route::get('/add-Product-brand', ProductBrandlist::class)->name('Product-brand');
        Route::get('/Product-category', ProductCategorylist::class)->name('Product-category');
        Route::get('/billing-page', BillingPage::class)->name('billing-page');
        Route::get('/manage-admin', ManageAdmin::class)->name('manage-admin');
        Route::get('/manage-staff', ManageStaff::class)->name('manage-staff');
        Route::get('/manage-customer', ManageCustomer::class)->name('manage-customer');
        Route::get('/Product-stock-details', ProductStockDetails::class)->name('Product-stock-details');
        Route::get('/staff-stock-details', StaffStockDetails::class)->name('staff-stock-details');


        Route::get('/staff-due-details', StaffDueDetails::class)->name('staff-due-details');
        Route::get('/customer-sale-details', CustomerSaleDetails::class)->name('customer-sale-details');

        Route::get('/view-payments', ViewPayments::class)->name('view-payments');
        Route::get('/admin/staff/{staffId}/reentry', \App\Livewire\Admin\StockReentry::class)->name('staff.reentry');
        Route::get('/store-billing', StoreBilling::class)->name('store-billing');
        Route::get('/print/sale/{id}', [PrintController::class, 'printSale'])->name('print.sale');
        Route::get('/print/sale/{id}/download', [PrintController::class, 'downloadSale'])->name('print.sale.download');
        Route::get('/print/quotation/{id}', [PrintController::class, 'printQuotation'])->name('quotation.print');
        Route::get('/due-payments', AdminDuePayments::class)->name('due-payments');
        Route::get('/staff-attendance', StaffAttendance::class)->name('staff-attendance');
        Route::get('/staff-salary', StaffSallary::class)->name('staff-salary');
        Route::get('/staff-salary-management', StaffSalary::class)->name('staff-salary-management');
        Route::get('/loan-management', LoanManage::class)->name('loan-management');
        Route::get('/sales-system', SalesSystem::class)->name('sales-system');
        Route::get('/staff-product-allocation', StaffProductAllocation::class)->name('staff-product-allocation');
        Route::get('/staff-allocated-list', StaffAllocatedList::class)->name('staff-allocated-list');
        Route::get('/staff-allocated-products/{staffId}', ViewStaffAllocatedProducts::class)->name('staff-allocated-products.view');
        Route::get('/staff-product-reentry/{staffId}', StaffProductReentry::class)->name('staff-product-reentry');
        Route::get('/staff-sales', StaffSalesView::class)->name('staff-sales');
        Route::get('/staff-sales-detail/{staffId}', \App\Livewire\Admin\StaffSalesDetail::class)->name('staff-sales-detail');
        Route::get('/staff-payment-approval', StaffPaymentApproval::class)->name('staff-payment-approval');
        Route::get('/pos-sales', PosSales::class)->name('pos-sales');

        Route::get('/supplier-management', SupplierManage::class)->name('supplier-management');
        Route::get('/quotation', Quotation::class)->name('quotation');
        Route::get('/goods-receive-note', GRN::class)->name('grn');
        Route::get('/expenses', Expenses::class)->name('expenses');
        Route::get('/day-summary', Income::class)->name('income');

        Route::get('/systemsetting', Settings::class)->name('systemsetting');
        Route::get('/reports', Reports::class)->name('reports');
        Route::get('/analytics', Analytics::class)->name('analytics');
        Route::get('/profit-loss', \App\Livewire\Admin\ProfitLoss::class)->name('profit-loss');
        Route::get('/quotation-system', QuotationSystem::class)->name('quotation-system');
        Route::get('/quotation-list', QuotationList::class)->name('quotation-list');
        Route::get('/sales-list', SalesList::class)->name('sales-list');
        Route::get('/settings', Settings::class)->name('settings');
        Route::get('/return-product', ReturnProduct::class)->name('return-product');
        Route::get('/purchase-order-list', PurchaseOrderList::class)->name('purchase-order-list');
        Route::get('/return-list', ReturnList::class)->name('return-list');
        Route::get('/add-customer-receipt', AddCustomerReceipt::class)->name('add-customer-receipt');
        Route::get('/cheque-list', ChequeList::class)->name('cheque-list');
        Route::get('/return-cheque', ReturnCheque::class)->name('return-cheque');
        Route::get('/list-customer-receipt', ListCustomerReceipt::class)->name('list-customer-receipt');
        Route::get('/add-supplier-receipt', AddSupplierReceipt::class)->name('add-supplier-receipt');
        Route::get('/list-supplier-receipt', ListSupplierReceipt::class)->name('list-supplier-receipt');
        Route::get('/return-supplier', ReturnSupplier::class)->name('return-supplier');
        Route::get('/list-supplier-return', ListSupplierReturn::class)->name('list-supplier-return');
        Route::get('/cash-deposit', DaySummary::class)->name('day-summary');
        Route::get('/deposits', Deposits::class)->name('deposits');
        Route::get('/register-report/{sessionId}', DaySummaryDetails::class)->name('day-summary-details');
        // Route to reopen today's closed POS session (AJAX)
        Route::post('/reopen-pos-session', [StoreBilling::class, 'reopenPOSSession'])->name('reopen-pos-session');
    });
    Route::post('/admin/update-cash', [CashController::class, 'updateCashInHand'])
        ->name('admin.updateCashInHand')
        ->middleware(['auth', 'role:admin']);

    Route::get('/admin/check-pos-session', [CashController::class, 'checkPOSSession'])
        ->name('admin.check-pos-session')
        ->middleware(['auth', 'role:admin']);

    //!! Staff routes - All admin routes available to staff (permissions control access)
    Route::middleware('role:staff')->prefix('staff')->name('staff.')->group(function () {
        // Dashboard
        Route::get('/dashboard', StaffDashboard::class)->name('dashboard');

        // Products
        Route::get('/Product-list', Products::class)->name('Productes');
        Route::get('/add-Product-brand', ProductBrandlist::class)->name('Product-brand');
        Route::get('/Product-category', ProductCategorylist::class)->name('Product-category');
        Route::get('/Product-stock-details', ProductStockDetails::class)->name('Product-stock-details');

        // Sales
        Route::get('/billing', Billing::class)->name('billing');
        Route::get('/billing-page', BillingPage::class)->name('billing-page');
        Route::get('/sales-system', \App\Livewire\Staff\StaffSalesSystem::class)->name('sales-system');
        Route::get('/pos-sales', PosSales::class)->name('pos-sales');
        Route::get('/sales-list', \App\Livewire\Staff\StaffSalesList::class)->name('sales-list');
        Route::get('/store-billing', StoreBilling::class)->name('store-billing');
        Route::get('/print/sale/{id}', [PrintController::class, 'printSale'])->name('print.sale');
        Route::get('/print/sale/{id}/download', [PrintController::class, 'downloadSale'])->name('print.sale.download');

        // Customers
        Route::get('/manage-customer', ManageCustomer::class)->name('manage-customer');
        Route::get('/customer-sale-details', CustomerSaleDetails::class)->name('customer-sale-details');
        Route::get('/customer-sale-management', CustomerSaleManagement::class)->name('customer-sale-management');

        // Stock/Inventory
        Route::get('/staff-stock-overview', StaffStockOverview::class)->name('staff-stock-overview');
        Route::get('/staff-stock-details', StaffStockDetails::class)->name('staff-stock-details');

        // Purchases
        Route::get('/goods-receive-note', GRN::class)->name('grn');
        Route::get('/purchase-order-list', PurchaseOrderList::class)->name('purchase-order-list');
        Route::get('/supplier-management', SupplierManage::class)->name('supplier-management');

        // Customers
        Route::get('/manage-customers', \App\Livewire\Staff\ManageCustomer::class)->name('manage-customers');

        // Quotations
        Route::get('/quotation-system', \App\Livewire\Staff\StaffQuotationSystem::class)->name('quotation-system');
        Route::get('/quotation-list', \App\Livewire\Staff\StaffQuotationList::class)->name('quotation-list');
        Route::get('/print/quotation/{id}', [PrintController::class, 'printQuotation'])->name('print.quotation');

        // Returns
        Route::get('/return-add', \App\Livewire\Staff\StaffReturnManagement::class)->name('return-add');
        Route::get('/return-list', \App\Livewire\Staff\StaffReturnList::class)->name('return-list');
        Route::get('/return-supplier', ReturnSupplier::class)->name('return-supplier');
        Route::get('/list-supplier-return', ListSupplierReturn::class)->name('list-supplier-return');

        // Expenses
        Route::get('/expenses', \App\Livewire\Staff\StaffExpenseManagement::class)->name('expenses');

        // Payments
        Route::get('/due-payments', \App\Livewire\Staff\AddPayment::class)->name('due-payments');
        Route::get('/payments-list', \App\Livewire\Staff\PaymentsList::class)->name('payments-list');
        Route::get('/view-payments', ViewPayments::class)->name('view-payments');
        Route::get('/add-customer-receipt', AddCustomerReceipt::class)->name('add-customer-receipt');
        Route::get('/list-customer-receipt', ListCustomerReceipt::class)->name('list-customer-receipt');
        Route::get('/add-supplier-receipt', AddSupplierReceipt::class)->name('add-supplier-receipt');
        Route::get('/list-supplier-receipt', ListSupplierReceipt::class)->name('list-supplier-receipt');

        // Cheques/Banks
        Route::get('/cheque-list', ChequeList::class)->name('cheque-list');
        Route::get('/return-cheque', ReturnCheque::class)->name('return-cheque');

        // Finance
        Route::get('/income', Income::class)->name('income');
        Route::get('/loan-management', LoanManage::class)->name('loan-management');

        // HR/Staff Management
        Route::get('/manage-staff', ManageStaff::class)->name('manage-staff');
        Route::get('/staff-attendance', StaffAttendance::class)->name('staff-attendance');
        Route::get('/staff-salary', StaffSallary::class)->name('staff-salary');
        Route::get('/staff-due-details', StaffDueDetails::class)->name('staff-due-details');

        // Reports & Analytics
        Route::get('/reports', Reports::class)->name('reports');
        Route::get('/analytics', Analytics::class)->name('analytics');

        // Settings
        Route::get('/settings', Settings::class)->name('settings');
    });

    // !! Export routes (accessible to authenticated users)
    Route::get('/Productes/export', [ProductsExportController::class, 'export'])->name('Productes.export');
    Route::get('/staff-sales/export', [StaffSaleExportController::class, 'export'])->name('staff-sales.export');

    // Receipt download (accessible to authenticated users)
    Route::get('/receipts/{id}/download', [ReceiptController::class, 'download'])->name('receipts.download');

    // Export staff stock details
    Route::get('/export/staff-stock', function () {
        return app(StaffStockDetails::class)->exportToCSV();
    })->name('export.staff-stock');

    // Product History Page - Using Livewire Component
    Route::get('/product-history/{id}', \App\Livewire\Admin\ProductHistory::class)->name('product-history');
});

// ============================================================================
// STAFF TYPE SPECIFIC ROUTES
// ============================================================================

// Admin Approval Routes (added to admin group)
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/sale-approval', SaleApproval::class)->name('sale-approval');
        Route::get('/payment-approval', PaymentApproval::class)->name('payment-approval');
        Route::get('/staff-expense-approval', \App\Livewire\Admin\StaffExpenseApproval::class)->name('staff-expense-approval');
    });

// Salesman Routes
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', 'staff_type:salesman'])
    ->prefix('salesman')
    ->name('salesman.')
    ->group(function () {
        Route::get('/dashboard', SalesmanDashboard::class)->name('dashboard');
        Route::get('/billing', SalesmanBilling::class)->name('billing');
        Route::get('/billing/{saleId}/edit', SalesmanBilling::class)->name('billing.edit');
        Route::get('/products', SalesmanProductList::class)->name('products');
        Route::get('/sales', SalesmanSalesList::class)->name('sales');
        Route::get('/customer-dues', SalesmanCustomerDues::class)->name('customer-dues');
        Route::get('/expenses', \App\Livewire\Salesman\SalesmanExpenses::class)->name('expenses');
    });

// Delivery Man Routes
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', 'staff_type:delivery_man'])
    ->prefix('delivery')
    ->name('delivery.')
    ->group(function () {
        Route::get('/dashboard', DeliveryManDashboard::class)->name('dashboard');
        Route::get('/pending', DeliveryManPendingDeliveries::class)->name('pending');
        Route::get('/completed', DeliveryManCompletedDeliveries::class)->name('completed');
        Route::get('/payments', DeliveryManPaymentCollection::class)->name('payments');
        Route::get('/payment-list', DeliveryManPaymentList::class)->name('payment-list');
        Route::get('/expenses', \App\Livewire\DeliveryMan\DeliveryManExpenses::class)->name('expenses');
        Route::get('/return-product', DeliveryManReturnProduct::class)->name('return-product');
        Route::get('/return-list', DeliveryManReturnList::class)->name('return-list');
    });

// Shop Staff Routes
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', 'staff_type:shop_staff'])
    ->prefix('shop-staff')
    ->name('shop-staff.')
    ->group(function () {
        Route::get('/dashboard', ShopStaffDashboard::class)->name('dashboard');
        Route::get('/products', ShopStaffProductList::class)->name('products');
    });
