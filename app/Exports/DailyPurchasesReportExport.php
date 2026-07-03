<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DailyPurchasesReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return PurchaseOrder::with(['supplier', 'items'])
            ->whereBetween('order_date', [$this->startDate, $this->endDate])
            ->orderBy('order_date', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Order Code',
            'Supplier',
            'Order Date',
            'Items Count',
            'Total Amount',
            'Status',
        ];
    }

    public function map($order): array
    {
        return [
            $order->order_code,
            $order->supplier->name ?? 'N/A',
            \Carbon\Carbon::parse($order->order_date)->format('M d, Y'),
            $order->items->count(),
            $order->total_amount,
            ucfirst($order->status),
        ];
    }
}
