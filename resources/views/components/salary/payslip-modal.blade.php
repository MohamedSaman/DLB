@props(['selectedSalary', 'errors' => null])
<div wire:ignore.self class="modal fade" id="payslip-modal" tabindex="-1" aria-labelledby="payslip-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="payslip-modal-label">
                    <i class="bi bi-file-earmark-person-fill me-2"></i>Employee Salary Slip
                </h5>
                <div class="ms-auto d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-light me-2" onclick="printPayslipContent()">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <button type="button" class="btn btn-sm btn-light me-2" onclick="downloadPayslip('Payslip.html')">
                        <i class="bi bi-download me-1"></i>Download
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-4" id="payslipContent">
                @if (!empty($selectedSalary) && $selectedSalary)
                @php
                $grossEarnings = ($selectedSalary->basic_salary ?? 0)+ ($selectedSalary->bonus ?? 0) + ($selectedSalary->overtime ?? 0);
                $totalDeductions = ($selectedSalary->deductions ?? 0) + ($selectedSalary->loan_deduction ?? 0);
                @endphp
                <div class="payslip-container">
                    <div class="text-center mb-4">
                        <h3 class="mb-0">DLB ENTERPRISES (PVT) LTD</h3>
                        <p class="mb-0 text-muted small">103 H,Yatiyanthota Road,Seethawaka,avissawella.</p>
                        <p class="mb-0 text-muted small">Phone: ( 076) 9085252| Email: Sample email </p>
                        <h4 class="mt-3 border-bottom border-2 pb-2">SALARY PAYSLIP</h4>
                    </div>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-7">
                                    <h6 class="text-muted mb-2">EMPLOYEE DETAILS</h6>
                                    <p class="mb-1"><strong>Name:</strong> {{ $selectedSalary->user->name ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Designation:</strong> {{ $selectedSalary->user->userDetail->work_role ?? 'N/A' }}</p>
                                    <p class="mb-0"><strong>Employee ID:</strong> {{ $selectedSalary->user->userDetail->user_id ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                                    <h6 class="text-muted mb-2">PAY PERIOD</h6>
                                    <p class="mb-1"><strong>Month:</strong> {{ !empty($selectedSalary->salary_month) ? \Carbon\Carbon::parse($selectedSalary->salary_month)->format('F, Y') : 'N/A' }}</p>
                                    <p class="mb-0"><strong>Payslip ID:</strong> #SL-{{ $selectedSalary->salary_id ?? 'N/A' }}</p>
                                    <p class="mb-0"><strong>Salary Status</strong> :
                                        @if($selectedSalary->payment_status === 'paid')
                                        <span class="badge bg-success text-white">Paid</span>
                                        @else
                                        <span class="badge bg-warning text-dark">Unpaid</span>
                                        @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted mb-2">Salary Details</h6>
                            <table class="table table-sm table-bordered">
                                <tbody>
                                    <tr>
                                        <td>Basic Salary</td>
                                        <td class="text-end">Rs.{{ number_format($selectedSalary->basic_salary - $selectedSalary->allowance ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Allowance</td>
                                        <td class="text-end">Rs.{{ number_format($selectedSalary->allowance ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Bonus</td>
                                        <td class="text-end">Rs.{{ number_format($selectedSalary->bonus ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Overtime</td>
                                        <td class="text-end">Rs.{{ number_format($selectedSalary->overtime ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Gross Earnings</td>
                                        <td class="text-end">Rs.{{ number_format($grossEarnings, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Loan Repayment</td>
                                        <td class="text-end">Rs.{{ number_format($selectedSalary->loan_deduction ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Other Deductions</td>
                                        <td class="text-end">Rs.{{ number_format($selectedSalary->deductions ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total Deductions</td>
                                        <td class="text-end text-danger">(Rs.{{ number_format($totalDeductions, 2) }})</td>
                                    </tr>
                                    <tr class="table-success fw-bold">
                                        <td>Net Salary Payable</td>
                                        <td class="text-end">Rs.{{ number_format($selectedSalary->net_salary ?? 0, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="alert alert-success text-center mt-3" role="alert">
                        <h5 class="alert-heading fw-bolder">NET SALARY PAYABLE</h5>
                        <hr>
                        <h3 class="display-6 fw-bold">Rs.{{ number_format($selectedSalary->net_salary ?? 0, 2) }}</h3>

                    </div>
                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="mb-0 text-muted small">This is a computer-generated payslip and does not require a signature.</p>
                        <p class="mb-0 text-muted small">Generated on: {{ now()->format('d/m/Y h:i A') }}</p>
                    </div>
                </div>
                @else
                <div class="text-center p-5">
                    <p class="text-muted">Loading payslip information...</p>
                </div>
                @endif
                @if($errors && $errors->has('form'))
                <div class="text-danger mt-2 small">{{ $errors->first('form') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>