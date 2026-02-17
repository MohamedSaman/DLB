<div class="pos-billing-terminal" wire:poll.10s>
    <!-- Load TailWind & Premium Fonts -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <style type="text/tailwindcss">
        @layer base {
            :root {
                /* Accent palette - tweak these for different color themes */
                --accent-50: #fff7ef;
                --accent-100: #ffefe0;
                --accent-300: #ffb58a;
                --accent-500: #ff7a2d; /* primary accent */
                --accent-700: #e65c00;
                --bg-pos: #f6f8fb;
                --muted: #94a3b8;
            }

            html, body { background: var(--bg-pos); font-family: 'Inter', sans-serif; color: #0f172a; }

            /* Scrollbar */
            .custom-scrollbar::-webkit-scrollbar { width: 6px; }
            .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
            .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 12px; }

            /* Material icons tuning */
            .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24; }

            /* Header */
            .pos-billing-terminal header { background: linear-gradient(90deg, var(--accent-50), #ffffff); border-bottom: 1px solid rgba(15,23,42,0.04); }
            .pos-billing-terminal header .bg-[#e67e22] { background: linear-gradient(135deg,var(--accent-500),var(--accent-700)); box-shadow: 0 6px 18px rgba(230,92,0,0.12); }

            /* Accent buttons */
            .btn-accent { background: linear-gradient(90deg,var(--accent-500),var(--accent-700)); color: white; }
            .btn-accent:hover { filter: brightness(.95); }

            /* Product cards */
            .product-card { @apply bg-white rounded-xl border border-slate-100 overflow-hidden shadow-md; }
            .product-card .card-body { @apply p-4; }
            .product-card img { @apply object-contain; }
            .product-card .price { color: var(--accent-700); font-weight: 800; }

            /* 'In stock' badge */
            .badge-instock { background: linear-gradient(90deg,#10b981,#06b6d4); color: white; font-weight: 700; padding: .25rem .5rem; border-radius: .375rem; font-size: .625rem; }

            /* Search input */
            .search-input { @apply w-full rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm; }

            /* Cart area */
            .cart-empty { color: var(--muted); }
            .cart-row { @apply transition-shadow; }
            .cart-row:hover { box-shadow: 0 6px 18px rgba(15,23,42,0.03); }

            /* Make the small remove button always visible (overrides hover-hidden) */
            .group\/thumb > button { opacity: 1 !important; transform: translate(0,0) !important; }

            /* Make small control buttons rounder and clearer */
            .qty-btn { @apply w-6 h-6 rounded-full text-[10px] font-bold bg-slate-100 border border-slate-200 flex items-center justify-center; }

            /* Product grid plus button */
            .add-btn { @apply w-9 h-9 rounded-lg bg-white border border-slate-200 shadow-sm flex items-center justify-center; }
            .add-btn:hover { transform: translateY(-3px); transition: transform .15s ease; }

            /* Receipt print styling tweak for in-browser modal */
            .receipt-container { border-radius: 12px; border: 1px solid rgba(15,23,42,0.04); }

            /* Footer action area */
            .pos-footer .btn { @apply rounded-lg px-6 py-3 font-black; }
            .pos-footer .btn-primary { background: linear-gradient(90deg,var(--accent-500),var(--accent-700)); color: white; }
        }
    </style>

    <div class="bg-slate-50 text-slate-800 h-screen flex flex-col overflow-hidden text-sm">
        
        <!-- Header Section -->
        <header class="bg-white border-b border-slate-200 px-4 py-2 flex items-center justify-between shadow-sm shrink-0">
            <div class="flex items-center gap-3">
                <div class=" p-1.5 rounded flex items-center">
                    <img src="{{ asset('images/HARDMEN.png') }}" class="h-10 w-auto t" alt="Logo">
                </div>
                {{--<div>
                    <h1 class="font-bold text-sm leading-tight tracking-tight text-slate-900 uppercase">
                        Hardmen <span class="text-[#e67e22]">(Pvt) Ltd</span>
                    </h1>
                    <p class="text-[10px] text-slate-500 font-medium tracking-wide">ADVANCED POS TERMINAL</p>
                </div>--}}
            </div>
            
            <div class="flex items-center gap-4">
                <div class="bg-slate-100 px-3 py-1 rounded border border-slate-200">
                    <span class="font-mono text-sm font-bold text-[#e67e22] tracking-widest" id="posClock">00:00:00</span>
                </div>
                <button class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 transition-colors text-[10px] font-bold text-slate-600 border border-slate-200"
                        wire:click="viewCloseRegisterReport">
                    <span class="material-symbols-outlined text-base">analytics</span>
                    POS REPORT
                </button>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex flex-1 overflow-hidden p-3 gap-3">
            
            <!-- LEFT SECTION: Search & Cart (50%) -->
            <aside class="w-1/2 flex flex-col bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                {{-- Search Bar with Alpine Keyboard Navigation --}}
                <div class="p-3 border-b border-slate-100 bg-slate-50/50"
                     x-data="{ highlightIndex: -1 }"
                     x-on:product-added-to-cart.window="
                         highlightIndex = -1;
                         $nextTick(() => {
                             const qtyInput = document.getElementById('cart-qty-0');
                             if (qtyInput) { qtyInput.focus(); qtyInput.select(); }
                         })
                     "
                     x-init="$nextTick(() => { if ($refs.searchInput) $refs.searchInput.focus(); })"
                >
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                        <input class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-md focus:ring-2 focus:ring-[#e67e22]/20 focus:border-[#e67e22] outline-none text-sm transition-all" 
                            x-ref="searchInput"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Scan barcode or type product name..." type="text"
                            x-on:keydown.arrow-down.prevent="
                                let items = document.querySelectorAll('[data-search-result]');
                                if (items.length > 0) {
                                    highlightIndex = (highlightIndex + 1) % items.length;
                                    items[highlightIndex]?.scrollIntoView({ block: 'nearest' });
                                }
                            "
                            x-on:keydown.arrow-up.prevent="
                                let items = document.querySelectorAll('[data-search-result]');
                                if (items.length > 0) {
                                    highlightIndex = highlightIndex <= 0 ? items.length - 1 : highlightIndex - 1;
                                    items[highlightIndex]?.scrollIntoView({ block: 'nearest' });
                                }
                            "
                            x-on:keydown.enter.prevent="
                                if (highlightIndex >= 0) {
                                    let items = document.querySelectorAll('[data-search-result]');
                                    if (items[highlightIndex]) items[highlightIndex].click();
                                    highlightIndex = -1;
                                }
                            "
                            x-on:keydown.escape.prevent="
                                highlightIndex = -1;
                                $wire.set('search', '');
                            "
                            x-on:input="highlightIndex = -1"
                        >

                        <!-- Search Dropdown -->
                        @if($search && count($searchResults) > 0)
                        <div class="absolute w-full mt-2 bg-white border border-slate-200 rounded-lg shadow-2xl z-50 max-h-96 overflow-y-auto custom-scrollbar">
                            @foreach($searchResults as $sIndex => $res)
                            <div class="flex items-center gap-3 p-3 cursor-pointer border-b border-slate-50 last:border-0 transition-colors"
                                data-search-result
                                data-search-index="{{ $sIndex }}"
                                :class="highlightIndex === {{ $sIndex }} ? 'bg-orange-50 border-l-2 !border-l-[#e67e22]' : 'hover:bg-slate-50'"
                                wire:click="addToCart({{ json_encode($res) }})"
                                x-on:mouseenter="highlightIndex = {{ $sIndex }}">
                                <img src="{{ $this->getImageUrl($res['image']) }}" 
                                    onerror="this.onerror=null;this.src='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSrn_80I-lMAa0pVBNmFmQ7VI6l4rr74JW-eQ&s';" 
                                    class="w-10 h-10 rounded object-cover border border-slate-100">
                                <div class="flex-1">
                                    <h5 class="text-xs font-bold text-slate-800">{{ $res['name'] }}</h5>
                                    <p class="text-[10px] text-slate-500 font-mono">
                                        {{ $res['code'] }} | 
                                        <span class="font-bold {{ $res['stock'] <= 5 ? 'text-amber-500' : 'text-green-600' }}">Available: {{ $res['stock'] }}</span>
                                        @if(($res['pending'] ?? 0) > 0)
                                            | <span class="font-bold text-orange-500">Pending: {{ $res['pending'] }}</span>
                                        @endif
                                    </p>
                                </div>
                                <span class="text-xs font-black text-[#e67e22]">Rs. {{ number_format($res['price'], 2) }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Cart Table --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-white z-10 border-b border-slate-100">
                            <tr class="text-[10px] uppercase font-bold text-slate-400">
                                <th class="px-3 py-2">Item Details</th>
                                <th class="px-2 py-2 w-32">Qty</th>
                                <th class="px-2 py-2 w-44">Price</th>
                                <th class="px-2 py-2 w-24">Disc (Rs/%)</th>
                                <th class="px-3 py-2 text-right">Subtotal</th>
                                <th class="px-2 py-2 w-12 text-right">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($cart as $index => $item)
                            @php $cartKey = $item['key'] ?? $index; @endphp
                            <tr class="group hover:bg-slate-50/80 transition-colors" wire:key="cart-{{ $cartKey }}">
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <div class="relative group/thumb">
                                            <img src="{{ $this->getImageUrl($item['image']) }}" 
                                                onerror="this.onerror=null;this.src='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSrn_80I-lMAa0pVBNmFmQ7VI6l4rr74JW-eQ&s';" 
                                                class="w-9 h-9 rounded border border-slate-200 object-cover">
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-xs font-bold text-slate-700 break-words" title="{{ $item['name'] }}">{{ $item['name'] }}</h4>
                                            <p class="text-[10px] text-slate-400 font-mono">{{ $item['code'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2 py-2">
                                    <div class="flex items-center gap-3">
                                        <button class="w-9 h-9 flex items-center justify-center hover:bg-white rounded text-[12px] font-bold transition-all bg-slate-100 border border-slate-200" wire:click="decrementQuantity({{ $index }})">-</button>

                                        <input type="number" min="1" step="1" max="{{ $item['stock'] ?? 0 }}" value="{{ $item['quantity'] }}" 
                                            id="cart-qty-{{ $index }}"
                                            wire:change="updateQuantity({{ $index }}, $event.target.value)" 
                                            wire:key="qty-{{ $cartKey }}" 
                                            @keydown.enter.prevent="
                                                $wire.updateQuantity({{ $index }}, $event.target.value);
                                                $nextTick(() => {
                                                    const searchInput = document.querySelector('[x-ref=searchInput]');
                                                    if (searchInput) { 
                                                        searchInput.focus(); 
                                                        searchInput.select();
                                                    }
                                                });
                                            "
                                            class="w-28 text-center text-[11px] font-black bg-slate-50 border border-slate-200 rounded px-3 py-2" />

                                        <button class="w-9 h-9 flex items-center justify-center hover:bg-white rounded text-[12px] font-bold transition-all bg-slate-100 border border-slate-200" wire:click="incrementQuantity({{ $index }})">+</button>

                                        <div class="text-[11px] text-slate-400 ml-2">
                                            <span class="font-mono font-bold {{ ($item['stock'] ?? 0) <= 5 ? 'text-amber-500' : 'text-green-600' }}">Avail: {{ $item['stock'] ?? 0 }}</span>
                                            @if(($item['pending'] ?? 0) > 0)
                                                | <span class="font-mono font-bold text-orange-500">Pending: {{ $item['pending'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2 py-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] font-bold text-slate-500">Rs.</span>
                                        <input type="number" step="0.01" min="0" value="{{ $item['price'] }}" 
                                            id="cart-price-{{ $index }}"
                                            wire:change="updatePrice({{ $index }}, $event.target.value)" 
                                            wire:key="price-{{ $cartKey }}"
                                            x-on:keydown.enter.prevent="$wire.updatePrice({{ $index }}, $event.target.value)"
                                            class="w-28 text-right text-[11px] font-bold bg-slate-50 border border-slate-200 rounded px-2 py-1" />
                                    </div>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    @php
                                        $discountType = $item['discount_type'] ?? 'fixed';
                                        $discountPercent = $item['discount_percentage'] ?? 0;
                                        $discountPerUnit = $item['discount'] ?? 0;
                                        $displayDiscount = '';
                                        if ($discountType === 'percentage' && $discountPercent > 0) {
                                            $displayDiscount = rtrim(rtrim(number_format($discountPercent, 2, '.', ''), '0'), '.') . '%';
                                        } elseif ($discountPerUnit > 0) {
                                            $displayDiscount = rtrim(rtrim(number_format($discountPerUnit, 2, '.', ''), '0'), '.');
                                        }
                                    @endphp
                                    <input type="text" 
                                        placeholder="0 or 0%" 
                                        value="{{ $displayDiscount }}"
                                        wire:change="updateDiscount({{ $index }}, $event.target.value)" 
                                        wire:key="disc-{{ $cartKey }}"
                                        class="w-full px-2 py-1 text-[10px] font-bold text-center bg-slate-50 border border-slate-200 rounded hover:border-[#e67e22]/30 focus:border-[#e67e22] focus:outline-none transition-all {{ $discountPerUnit > 0 ? 'text-green-600 bg-green-50/50' : 'text-slate-400' }}" />
                                    @if($discountPerUnit > 0)
                                        <div class="text-[9px] text-green-600 mt-0.5 font-mono">
                                            @if($discountType === 'percentage' && $discountPercent > 0)
                                                {{ rtrim(rtrim(number_format($discountPercent, 2, '.', ''), '0'), '.') }}% = -Rs.{{ number_format($discountPerUnit * $item['quantity'], 0) }}
                                            @else
                                                -Rs.{{ number_format($discountPerUnit * $item['quantity'], 0) }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <p class="text-xs font-black text-slate-800 tracking-tight">Rs. {{ number_format($item['total'], 0) }}</p>
                                </td>
                                <td class="px-2 py-2 w-12 text-right">
                                    <button class="bg-red-500 text-white rounded-full p-1.5 shadow-sm hover:bg-red-600 transition-colors" wire:click="removeFromCart({{ $index }})" title="Remove">
                                        <span class="material-symbols-outlined text-[12px] font-black">close</span>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-24 text-center">
                                    <div class="bg-slate-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                        <span class="material-symbols-outlined text-4xl text-slate-200">shopping_cart_off</span>
                                    </div>
                                    <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Your cart is empty</p>
                                    <p class="text-slate-300 text-[9px] mt-1">Scan or search products to begin</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Left Footer: Totals --}}
                <div class="p-4 bg-slate-50 border-t border-slate-200 bg-gradient-to-b from-slate-50/50 to-white">
                    <div class="grid grid-cols-2 gap-4 items-end mb-4">
                        <div class="space-y-1.5">
                            @php
                                $originalSubtotal = collect($cart)->sum(function ($item) {
                                    return ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
                                });
                                $unitDiscountRs = collect($cart)->sum(function ($item) {
                                    return ($item['discount'] ?? 0) * ($item['quantity'] ?? 0);
                                });
                                $globalDiscountAmount = $additionalDiscountAmount ?? 0;
                                $totalDiscountRs = max(0, $unitDiscountRs + $globalDiscountAmount);
                                $totalDiscountPercent = $originalSubtotal > 0 ? (($totalDiscountRs / $originalSubtotal) * 100) : 0;
                            @endphp
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-400 font-semibold">Subtotal (Before Discount)</span>
                                <span class="font-bold text-slate-700">Rs. {{ number_format($originalSubtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-400 font-semibold">Unit Discount</span>
                                <span class="font-bold text-red-500">- Rs. {{ number_format($unitDiscountRs, 2) }}</span>
                            </div>

                            @if($globalDiscountAmount > 0)
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-400 font-semibold">Global Discount</span>
                                <span class="font-bold text-amber-600">
                                    - Rs. {{ number_format($globalDiscountAmount, 2) }}
                                    @if(($additionalDiscountType ?? 'fixed') === 'percentage' && ($additionalDiscount ?? 0) > 0)
                                        <span class="text-slate-400 font-semibold">({{ number_format($additionalDiscount, 2) }}%)</span>
                                    @endif
                                </span>
                            </div>
                                                        <div class="flex justify-between text-xs">
                                <span class="text-slate-400 font-semibold">Total Discount</span>
                                <span class="font-bold text-red-500">- Rs. {{ number_format($totalDiscountRs, 2) }} @if($totalDiscountPercent > 0)<span class="text-slate-400 font-semibold">({{ number_format($totalDiscountPercent, 2) }}%)</span>@endif</span>
                            </div>
                            @endif
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-400 font-semibold">Tax (0%)</span>
                                <span class="font-bold text-slate-700">Rs. 0.00</span>
                            </div>
                        </div>
                        <div>
                            <button class="w-full py-2 bg-white border border-slate-200 rounded text-[10px] font-black flex items-center justify-center gap-2 hover:bg-slate-50 hover:border-[#e67e22]/50 transition-all text-slate-600 shadow-sm uppercase tracking-tighter"
                                wire:click="openSaleDiscountModal">
                                <span class="material-symbols-outlined text-base">sell</span>
                                APPLY GLOBAL DISCOUNT
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 items-center border-t border-slate-200 pt-4">
                        <div class="flex justify-between items-baseline">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total</span>
                            <span class="text-3xl font-black text-[#e67e22] tracking-tighter">Rs. {{ number_format($grandTotal, 2) }}</span>
                        </div>
                        <button class="w-full bg-[#e67e22] hover:bg-orange-600 text-white font-black py-3 rounded-lg flex items-center justify-center gap-2 shadow-xl shadow-orange-500/20 transition-all text-xs uppercase tracking-widest disabled:opacity-40 disabled:grayscale disabled:cursor-not-allowed group"
                            wire:click="validateAndCreateSale" {{ count($cart) == 0 ? 'disabled' : '' }}>
                            <span class="material-symbols-outlined text-xl group-hover:scale-110 transition-transform">payments</span>
                            Complete Sale
                        </button>
                    </div>
                </div>
            </aside>

            <!-- RIGHT SECTION: Selections & Product Grid (50%) -->
            <section class="w-1/2 flex flex-col gap-3 overflow-hidden">
                {{-- Selection Box (Customer/Price) --}}
                <div class="bg-white p-3 rounded-lg shadow-sm border border-slate-200 space-y-3">
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase mb-1.5 block tracking-widest">Customer Selection</label>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg group-focus-within:text-[#e67e22] transition-colors">person</span>
                                <select class="w-full pl-9 pr-10 py-2 bg-slate-50 border border-slate-200 rounded-md outline-none text-xs font-bold appearance-none focus:ring-2 focus:ring-[#e67e22]/10 focus:border-[#e67e22] transition-all" wire:model.live="customerId">
                                    @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->business_name ?? $customer->name }} ({{ $customer->phone }})</option>
                                    @endforeach
                                </select>
                                <button class="absolute right-8 top-1/2 -translate-y-1/2 text-[#e67e22] p-1.5 hover:bg-orange-50 rounded-full transition-all" wire:click="openCustomerModal" title="Add Customer">
                                    <span class="material-symbols-outlined text-lg">person_add</span>
                                </button>
                                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-300 text-lg pointer-events-none">expand_more</span>
                            </div>
                        </div>
                        <div class="w-1/3">
                            <label class="text-[10px] font-black text-slate-400 uppercase mb-1.5 block tracking-widest">Price Type</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">sell</span>
                                <select class="w-full pl-9 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-md outline-none text-xs font-bold appearance-none focus:border-[#e67e22] transition-all" wire:model.live="priceType">
                                    <option value="retail">Retail Price</option>
                                    <option value="wholesale">Wholesale</option>
                                    <option value="distribute">Distribute Price</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-300 text-lg pointer-events-none">expand_more</span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Customer Balance Information (Conditional) --}}
                    @if($customerId && $customerId != '' && $selectedCustomer && $selectedCustomer->type != 'Walking Customer')
                    <div class="p-3 bg-gradient-to-r from-slate-50 to-slate-100 border border-slate-200 rounded-lg grid grid-cols-4 gap-2">
                        <div class="text-center p-2 bg-white rounded border border-slate-100">
                            <div class="text-[8px] font-black text-slate-400 uppercase tracking-wider mb-1">Opening Balance</div>
                            <div class="text-sm font-black text-slate-800">{{ number_format($customerOpeningBalanceDisplay, 2) }}</div>
                        </div>
                        <div class="text-center p-2 bg-white rounded border border-slate-100">
                            <div class="text-[8px] font-black text-slate-400 uppercase tracking-wider mb-1">Due Amount</div>
                            <div class="text-sm font-black text-amber-600">{{ number_format($customerDueAmountDisplay, 2) }}</div>
                        </div>
                        <div class="text-center p-2 bg-white rounded border border-slate-100">
                            <div class="text-[8px] font-black text-slate-400 uppercase tracking-wider mb-1">Overpaid</div>
                            <div class="text-sm font-black text-green-600">{{ number_format($customerOverpaidAmountDisplay, 2) }}</div>
                        </div>
                        <div class="text-center p-2 bg-white rounded border border-slate-100">
                            <div class="text-[8px] font-black text-slate-400 uppercase tracking-wider mb-1">Total Due</div>
                            <div class="text-sm font-black text-slate-800">{{ number_format($customerTotalDueDisplay, 2) }}</div>
                        </div>
                    </div>
                    @endif
                    
                    {{-- Filter Buttons --}}
                    <div class="grid grid-cols-2 gap-3">
                        <button class="flex items-center justify-center gap-2 py-2.5 bg-slate-100 border border-slate-200 rounded-md hover:bg-slate-200 hover:border-slate-300 transition-all font-black text-[10px] text-slate-600 uppercase tracking-tighter shadow-sm"
                            wire:click="toggleCategoryPanel">
                            <span class="material-symbols-outlined text-lg text-[#e67e22]">category</span>
                            FILTER BY CATEGORY
                        </button>
                        <button class="flex items-center justify-center gap-2 py-2.5 bg-slate-100 border border-slate-200 rounded-md hover:bg-slate-200 hover:border-slate-300 transition-all font-black text-[10px] text-slate-600 uppercase tracking-tighter shadow-sm"
                            wire:click="toggleBrandPanel">
                            <span class="material-symbols-outlined text-lg text-[#e67e22]">branding_watermark</span>
                            FILTER BY BRAND
                        </button>
                    </div>
                </div>

                {{-- Product Grid Area --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar pr-1">
                    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2 pb-4">
                        @forelse($products as $product)
                        @php
                            $isLow = ($product['stock'] ?? 0) <= 5 && ($product['stock'] ?? 0) > 0;
                            $isOut = ($product['stock'] ?? 0) <= 0;
                        @endphp
                        <div class="group bg-white border border-slate-200 rounded-lg shadow-sm hover:border-[#e67e22]/60 hover:shadow-md transition-all cursor-pointer relative flex flex-col h-full overflow-hidden"
                             wire:click="addToCart({{ json_encode($product) }})">
                            
                            {{-- Batch Status --}}
                            <div class="absolute top-1.5 right-1.5 z-10">
                                @if($isOut)
                                    <span class="bg-red-500 text-[8px] text-white font-black px-1.5 py-0.5 rounded-sm uppercase tracking-tighter">Out of Stock</span>
                                @elseif($isLow)
                                    <span class="bg-amber-500 text-[8px] text-white font-black px-1.5 py-0.5 rounded-sm uppercase tracking-tighter">Low Stock</span>
                                @else
                                    <span class="bg-green-500 text-[8px] text-white font-black px-1.5 py-0.5 rounded-sm uppercase tracking-tighter">In Stock</span>
                                @endif
                            </div>

                            {{-- Product Image --}}
                            <div class="aspect-square bg-slate-50 flex items-center justify-center p-3">
                                <img src="{{ $this->getImageUrl($product['image']) }}" 
                                    onerror="this.onerror=null;this.src='https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSrn_80I-lMAa0pVBNmFmQ7VI6l4rr74JW-eQ&s';" 
                                    class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500" 
                                    alt="{{ $product['name'] }}">
                            </div>

                            {{-- Product Details --}}
                            <div class="p-2.5 flex flex-col flex-1 bg-white">
                                <p class="text-[9px] text-slate-400 font-mono uppercase mb-0.5">{{ $product['code'] }}</p>
                                <h3 class="text-[11px] font-bold text-slate-800 leading-tight mb-2 break-words" title="{{ $product['name'] }}">{{ $product['name'] }}</h3>
                                
                                <div class="mt-auto flex items-end justify-between">
                                    <div class="flex flex-col">
                                        <span class="text-[#e67e22] font-black text-sm leading-none tracking-tighter">Rs. {{ number_format($product['price'], 0) }}</span>
                                        <span class="text-[9px] text-slate-400 font-bold mt-1.5">
                                            <span class="{{ ($product['stock'] ?? 0) <= 5 ? 'text-amber-500' : 'text-green-600' }}">Avail: {{ $product['stock'] }}</span>
                                            @if(($product['pending'] ?? 0) > 0)
                                                | <span class="text-orange-500">Pend: {{ $product['pending'] }}</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="bg-slate-100 p-1.5 rounded-md group-hover:bg-[#e67e22] group-hover:text-white transition-all shadow-sm">
                                        <span class="material-symbols-outlined text-base font-black">add</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-span-full py-32 text-center">
                            <div class="bg-slate-50 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                <span class="material-symbols-outlined text-5xl text-slate-200">inventory_2</span>
                            </div>
                            <p class="text-slate-300 font-black uppercase tracking-widest text-xs">No products in this category</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </main>
    </div>


    {{-- Sliding Category Sidebar (Right to Left, 50% width) --}}
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[2000] transition-opacity duration-300 {{ $showCategoryPanel ? 'opacity-100' : 'opacity-0 pointer-events-none' }}" wire:click.self="$set('showCategoryPanel', false)"></div>
    <aside class="fixed right-0 top-0 bottom-0 w-1/2 bg-white z-[2001] shadow-2xl transition-transform duration-300 transform {{ $showCategoryPanel ? 'translate-x-0' : 'translate-x-full' }} flex flex-col">
        <div class="p-4 flex justify-between items-center border-b border-slate-100 bg-slate-50">
            <h6 class="mb-0 font-black text-xs text-slate-800 tracking-widest"><i class="material-symbols-outlined align-middle mr-2 text-[#e67e22]">grid_view</i>ALL CATEGORIES</h6>
            <button class="text-slate-400 hover:text-slate-600 transition-colors" wire:click="$set('showCategoryPanel', false)">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-2 overflow-y-auto flex-1 custom-scrollbar">
            <button class=" mb-1 text-center  p-3 rounded-lg transition-all border border-slate-100 {{ !$selectedCategory ? 'bg-[#e67e22] text-white shadow-lg shadow-orange-500/30' : 'hover:bg-slate-100 text-slate-600' }}"
                wire:click="showAllProducts">
                <span class="font-black text-xs tracking-tight">Show All Items</span>
                <span class="text-[10px] font-bold opacity-70">{{ count($products) }}</span>
            </button>
            @foreach($categories as $category)
            <button class=" mb-1 text-center p-3 rounded-lg transition-all border border-slate-100 {{ $selectedCategory == $category->id ? 'bg-[#e67e22] text-white shadow-lg shadow-orange-500/30' : 'hover:bg-slate-100 text-slate-600' }}"
                wire:click="selectCategory({{ $category->id }})">
                <span class="font-black text-xs tracking-tight">{{ $category->category_name }}</span>
                <span class="text-[10px] font-bold opacity-70">{{ $category->products_count }}</span>
            </button>
            @endforeach
        </div>
    </aside>

    {{-- Sliding Brand Sidebar (Right to Left, 50% width) --}}
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[2000] transition-opacity duration-300 {{ $showBrandPanel ? 'opacity-100' : 'opacity-0 pointer-events-none' }}" wire:click.self="$set('showBrandPanel', false)"></div>
    <aside class="fixed right-0 top-0 bottom-0 w-1/2 bg-white z-[2001] shadow-2xl transition-transform duration-300 transform {{ $showBrandPanel ? 'translate-x-0' : 'translate-x-full' }} flex flex-col">
        <div class="p-4 flex justify-between items-center border-b border-slate-100 bg-slate-50">
            <h6 class="mb-0 font-black text-xs text-slate-800 tracking-widest"><i class="material-symbols-outlined align-middle mr-2 text-[#e67e22]">local_offer</i>ALL BRANDS</h6>
            <button class="text-slate-400 hover:text-slate-600 transition-colors" wire:click="$set('showBrandPanel', false)">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-2 overflow-y-auto flex-1 custom-scrollbar">
            <button class=" mb-1 text-center  p-3 rounded-lg transition-all border border-slate-100 {{ !$selectedBrand ? 'bg-[#e67e22] text-white shadow-lg shadow-orange-500/30' : 'hover:bg-slate-100 text-slate-600' }}"
                wire:click="showAllBrands">
                <span class="font-black text-xs tracking-tight">Show All Brands</span>
                <span class="text-[10px] font-bold opacity-70">{{ count($products) }}</span>
            </button>
            @foreach($brands as $brand)
            <button class=" mb-1 text-center p-3 rounded-lg transition-all border border-slate-100 {{ $selectedBrand == $brand['id'] ? 'bg-[#e67e22] text-white shadow-lg shadow-orange-500/30' : 'hover:bg-slate-100 text-slate-600' }}"
                wire:click="selectBrand({{ $brand['id'] }})">
                <span class="font-black text-xs tracking-tight">{{ $brand['brand_name'] }}</span>
                <span class="text-[10px] font-bold opacity-70">{{ $brand['products_count'] }}</span>
            </button>
            @endforeach
        </div>
    </aside>

    {{-- MODALS WRAPPER --}}
    @if($showPaymentModal || $showSaleDiscountModal || $showCustomerModal || $showSaleModal || $showCloseRegisterModal)
    <div class="fixed inset-0 z-[3000] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
        
        {{-- CUSTOMER MODAL --}}
        @if($showCustomerModal)
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative transform transition-all">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-black text-xs uppercase tracking-widest text-[#e67e22]"><i class="material-symbols-outlined align-middle mr-2">person_add</i>ADD NEW CUSTOMER</h3>
                <button class="text-slate-400 hover:text-slate-600" wire:click="closeCustomerModal"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Full Name *</label>
                    <input type="text" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold" wire:model="customerName" placeholder="Enter name...">
                    @error('customerName') <span class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Phone Number * </label>
                    <input type="text" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold" wire:model="customerPhone" placeholder="07xxxxxxxx or 07xxxx, 07yyyy / 09zzzz">
                    @error('customerPhone') <span class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Customer Type *</label>
                    <select class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold" wire:model="customerType">
                        <option value="">-- Select Type --</option>
                        <option value="retail">Retail</option>
                        <option value="wholesale">Wholesale</option>
                        <option value="distributor">Distributor</option>
                    </select>
                    @error('customerType') <span class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="col-span-2">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Email Address</label>
                    <input type="email" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold" wire:model="customerEmail" placeholder="email@example.com">
                    @error('customerEmail') <span class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="col-span-2">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Billing Address</label>
                    <textarea class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold" wire:model="customerAddress" rows="2" placeholder="Address..."></textarea>
                </div>
                <div class="col-span-2">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Business Name</label>
                    <input type="text" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold" wire:model="businessName" placeholder="Business name...">
                </div>
                
                {{-- More Information Toggle Button --}}
                <div class="col-span-2">
                    <button type="button" class="flex items-center gap-2 px-3 py-2 border border-slate-200 rounded-lg bg-white hover:bg-slate-50 transition-colors text-xs font-bold text-slate-600"
                            wire:click="$toggle('showCustomerMoreInfo')">
                        <span class="material-symbols-outlined text-base transition-transform" style="transform: rotateZ({{ $showCustomerMoreInfo ? '180' : '0' }})deg)">
                            expand_more
                        </span>
                        More Information
                    </button>
                </div>

                {{-- More Information Section (Conditional) --}}
                @if($showCustomerMoreInfo)
                <div class="col-span-2 space-y-3 pt-2 border-t border-slate-200">
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Opening Balance</label>
                        <input type="number" step="0.01" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold" 
                               wire:model="customerOpeningBalance" placeholder="0.00">
                        @error('customerOpeningBalance') <span class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</span> @enderror
                        <small class="text-slate-500 text-[8px] mt-1">Amount customer owes at the start</small>
                    </div>
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Overpaid Amount</label>
                        <input type="number" step="0.01" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold" 
                               wire:model="customerOverpaidAmount" placeholder="0.00">
                        @error('customerOverpaidAmount') <span class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</span> @enderror
                        <small class="text-slate-500 text-[8px] mt-1">Advance payment from customer</small>
                    </div>
                </div>
                @endif
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
                <button class="px-6 py-2.5 text-[10px] font-black uppercase text-slate-400" wire:click="closeCustomerModal">Discard</button>
                <button class="px-8 py-2.5 bg-[#e67e22] text-white rounded-lg text-[10px] font-black uppercase shadow-lg shadow-orange-500/20" wire:click="createCustomer">Save Customer</button>
            </div>
        </div>
        @endif

        {{-- SALE DISCOUNT MODAL --}}
        @if($showSaleDiscountModal)
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative transform transition-all">
            <div class="p-6 text-center border-b border-slate-100">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Apply Sale Discount</h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex gap-2 p-1 bg-slate-100 rounded-xl">
                    <button class="flex-1 py-2 text-[10px] font-black uppercase rounded-lg transition-all {{ $saleDiscountType == 'fixed' ? 'bg-white text-[#e67e22] shadow-sm' : 'text-slate-400' }}"
                        wire:click="$set('saleDiscountType', 'fixed')">Fixed Amount</button>
                    <button class="flex-1 py-2 text-[10px] font-black uppercase rounded-lg transition-all {{ $saleDiscountType == 'percentage' ? 'bg-white text-[#e67e22] shadow-sm' : 'text-slate-400' }}"
                        wire:click="$set('saleDiscountType', 'percentage')">Percentage (%)</button>
                </div>
                <div class="space-y-2">
                    <div class="relative">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">{{ $saleDiscountType == 'percentage' ? '%' : 'Rs.' }}</span>
                        <input type="number" 
                            step="0.01"
                            min="0"
                            max="{{ $saleDiscountType == 'percentage' ? '100' : '' }}"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xl font-black text-slate-700 outline-none focus:border-[#e67e22]" 
                            wire:model.live="saleDiscountValue"
                            placeholder="0">
                    </div>
                    {{-- Validation Helper Text --}}
                    <div class="text-[9px] font-bold text-slate-500 px-1">
                        @if($saleDiscountType == 'percentage')
                            Max: <span class="text-[#e67e22]">100%</span>
                        @else
                            Max: <span class="text-[#e67e22]">Rs. {{ number_format($subtotalAfterItemDiscounts, 2) }}</span> (Sale Total)
                        @endif
                    </div>
                </div>
            </div>
            <div class="p-4 bg-slate-50 flex gap-2">
                <button class="flex-1 py-3 text-[10px] font-black uppercase text-slate-400" wire:click="$set('showSaleDiscountModal', false)">Cancel</button>
                <button class="flex-1 py-3 bg-[#e67e22] text-white rounded-xl text-[10px] font-black uppercase shadow-lg shadow-orange-500/10"
                    wire:click="applySaleDiscount">Apply Discount</button>
            </div>
        </div>
        @endif

        {{-- PAYMENT MODAL --}}
        @if($showPaymentModal)
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl h-[85vh] overflow-hidden relative transform transition-all flex flex-col">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
                <h3 class="font-black text-xs uppercase tracking-widest text-[#e67e22]"><i class="material-symbols-outlined align-middle mr-2">payment_confirmed</i>Secure Transaction</h3>
                <button class="text-slate-400 hover:text-slate-600" wire:click="closePaymentModal"><span class="material-symbols-outlined">close</span></button>
            </div>
            
            <div class="flex-1 flex min-h-0">
                {{-- Payment Methods (Left) --}}
                <div class="w-2/5 border-r border-slate-100 p-6 space-y-4 overflow-y-auto">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Choose Method</label>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['cash' => 'payments', 'multiple' => 'paid', 'cheque' => 'check_box', 'credit' => 'history'] as $id => $icon)
                        <div class="group relative">
                            <input type="radio" value="{{ $id }}" wire:model.live="paymentMethod" id="pay_{{ $id }}" class="sr-only">
                            <label for="pay_{{ $id }}" class="flex flex-col items-center justify-center p-4 border-2 rounded-xl cursor-pointer transition-all {{ $paymentMethod == $id ? 'border-[#e67e22] bg-orange-50/50 text-[#e67e22]' : 'border-slate-100 grayscale hover:bg-slate-50 hover:grayscale-0' }}">
                                <span class="material-symbols-outlined text-3xl mb-2">{{ $icon }}</span>
                                <span class="text-[10px] font-black uppercase">{{ ucfirst(str_replace('_', ' ', $id)) }}</span>
                                @if($paymentMethod == $id)
                                    <span class="absolute top-1 right-1 material-symbols-outlined text-[#e67e22] text-base">check_circle</span>
                                @endif
                            </label>
                        </div>
                        @endforeach
                    </div>

                    {{-- Form Specifics --}}
                    <div class="mt-8 space-y-4 pt-4 border-t border-slate-50">
                        @if($paymentMethod == 'cash')
                            <div>
                                <label class="text-[9px] font-black text-slate-400 uppercase block mb-2">Amount Received</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rs.</span>
                                    <input type="number" class="w-full pl-12 pr-4 py-4 bg-white border-2 border-slate-100 rounded-xl text-3xl font-black text-[#e67e22] outline-none" wire:model.live="amountReceived">
                                </div>
                            </div>
                            <div class="p-4 bg-green-50 rounded-xl border border-green-100 flex items-center justify-between">
                                <span class="text-[10px] font-black text-green-600 uppercase tracking-widest">Balance to Return</span>
                                <span class="text-xl font-black text-green-700">Rs. {{ number_format(max(0, ($amountReceived ?? 0) - $grandTotal), 2) }}</span>
                            </div>
                        @elseif($paymentMethod == 'multiple')
                            <div class="space-y-3">
                                <div>
                                    <label class="text-[9px] font-black text-slate-400 uppercase mb-1 block">Cash Amount</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rs.</span>
                                        <input type="number" step="0.01" min="0" max="{{ max(0, $grandTotal - collect($cheques)->sum('amount')) }}" class="w-full pl-12 pr-4 py-4 bg-white border-2 border-slate-100 rounded-xl text-3xl font-black text-[#e67e22] outline-none" wire:model.live="cashAmount">
                                    </div>
                                    <div class="mt-2 text-[10px] text-slate-400">Remaining to cover: <span class="font-black text-slate-700">Rs. {{ number_format(max(0, $grandTotal - collect($cheques)->sum('amount')), 2) }}</span></div>
                                </div>

                                <div>
                                    <div class="flex justify-between items-center">
                                        <label class="text-[9px] font-black text-slate-400 uppercase mb-1 block">Cheques</label>
                                        <button class="text-[10px] font-black text-[#e67e22]" wire:click="toggleChequeForm">{{ $expandedChequeForm ? 'Cancel' : '+ Add Cheque' }}</button>
                                    </div>

                                    @if(count($cheques) > 0)
                                    <div class="space-y-2 mt-2">
                                        @foreach($cheques as $i => $c)
                                        <div class="flex items-center justify-between bg-white border border-slate-100 rounded p-2">
                                            <div class="text-xs">
                                                <div class="font-bold">{{ $c['bank_name'] ?? '-' }}</div>
                                                <div class="text-[11px] text-slate-500">#{{ $c['number'] ?? '-' }} • Rs. {{ number_format($c['amount'],2) }}</div>
                                            </div>
                                            <button class="text-red-500 text-sm" wire:click="removeCheque({{ $i }})">Remove</button>
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <p class="text-[11px] text-slate-400 mt-2">No cheques linked yet</p>
                                    @endif

                                    @if($expandedChequeForm)
                                    <div class="space-y-2 mt-3">
                                        <input type="text" class="w-full p-2 bg-white border border-slate-200 rounded text-xs font-bold" wire:model="tempBankName" placeholder="Bank Name">
                                        <input type="text" class="w-full p-2 bg-white border border-slate-200 rounded text-xs font-bold" wire:model="tempChequeNumber" placeholder="Cheque #">
                                        <input type="date" class="w-full p-2 bg-white border border-slate-200 rounded text-xs" wire:model="tempChequeDate">
                                        <input type="number" class="w-full p-2 bg-white border border-slate-200 rounded text-xs font-bold" wire:model="tempChequeAmount" placeholder="Amount">
                                        <button class="w-full py-2 bg-[#e67e22] text-white rounded-lg text-[10px] font-black uppercase" wire:click="addCheque">Add Cheque</button>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @elseif($paymentMethod == 'cheque')
                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                                <button class="w-full py-2 bg-white border border-slate-200 rounded-lg text-[10px] font-black uppercase text-[#e67e22] shadow-sm mb-3" wire:click="toggleChequeForm">
                                    {{ $expandedChequeForm ? 'Cancel Form' : '+ Add Cheque Details' }}
                                </button>
                                @if($expandedChequeForm)
                                <div class="space-y-2">
                                    <input type="text" class="w-full p-2 bg-white border border-slate-200 rounded text-xs font-bold" wire:model="tempBankName" placeholder="Bank Name">
                                    <input type="text" class="w-full p-2 bg-white border border-slate-200 rounded text-xs font-bold" wire:model="tempChequeNumber" placeholder="Cheque #">
                                    <input type="date" class="w-full p-2 bg-white border border-slate-200 rounded text-xs" wire:model="tempChequeDate">
                                    <input type="number" class="w-full p-2 bg-white border border-slate-200 rounded text-xs font-bold" wire:model="tempChequeAmount" placeholder="Amount">
                                    <button class="w-full py-2 bg-[#e67e22] text-white rounded-lg text-[10px] font-black uppercase" wire:click="addCheque">Link Cheque</button>
                                </div>
                                @endif

                                {{-- Display Added Cheques --}}
                                @if(count($cheques) > 0)
                                <div class="mt-4 space-y-2">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Added Cheques ({{ count($cheques) }})</label>
                                    @foreach($cheques as $index => $cheque)
                                    <div class="bg-white border border-slate-200 rounded-lg p-3 flex items-center justify-between group hover:border-orange-200 transition-all">
                                        <div class="flex-1 space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-[#e67e22] text-sm">check_box</span>
                                                <span class="font-black text-xs text-slate-800">{{ $cheque['bank_name'] }}</span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 text-[10px] text-slate-500 font-bold ml-6">
                                                <div><span class="text-slate-400">Cheque #:</span> {{ $cheque['number'] }}</div>
                                                <div><span class="text-slate-400">Date:</span> {{ date('d/m/Y', strtotime($cheque['date'])) }}</div>
                                            </div>
                                            <div class="text-xs font-black text-[#e67e22] ml-6">Rs. {{ number_format($cheque['amount'], 2) }}</div>
                                        </div>
                                        <button class="opacity-0 group-hover:opacity-100 transition-opacity p-2 hover:bg-red-50 rounded-lg" wire:click="removeCheque({{ $index }})" title="Remove Cheque">
                                            <span class="material-symbols-outlined text-red-500 text-lg">delete</span>
                                        </button>
                                    </div>
                                    @endforeach
                                    
                                    {{-- Total Cheques Amount --}}
                                    <div class="mt-3 p-3 bg-orange-50 border border-orange-100 rounded-lg flex justify-between items-center">
                                        <span class="text-[10px] font-black text-orange-600 uppercase tracking-widest">Total Cheque Amount</span>
                                        <span class="text-lg font-black text-[#e67e22]">Rs. {{ number_format(collect($cheques)->sum('amount'), 2) }}</span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        @elseif($paymentMethod == 'credit')
                            <div class="p-6 text-center border-2 border-dashed border-amber-200 rounded-2xl bg-amber-50/50">
                                <span class="material-symbols-outlined text-4xl text-amber-500 mb-2">crisis_alert</span>
                                <h4 class="text-xs font-black uppercase text-amber-700 tracking-widest">Authorized Credit Sale</h4>
                                <p class="text-[10px] text-amber-600 font-bold mt-2 leading-relaxed">The total of Rs. {{ number_format($grandTotal, 2) }} will be recorded against the customer's credit profile.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Order Summary (Right) --}}
                <div class="w-3/5 bg-slate-50/50 p-8 flex flex-col overflow-y-auto">
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Order Breakdown</h4>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-slate-500 font-bold text-xs uppercase">Customer</span>
                            <span class="font-black text-slate-800 text-xs">{{ $selectedCustomer->name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-slate-500 font-bold text-xs uppercase">Items Total</span>
                            <span class="font-bold text-slate-800 text-xs">Rs. {{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-slate-500 font-bold text-xs uppercase">Adjustments</span>
                            <span class="font-bold text-red-500 text-xs">-Rs. {{ number_format($additionalDiscountAmount + $totalDiscount, 2) }}</span>
                        </div>
                        <div class="mt-4 p-6 bg-white rounded-2xl shadow-xl shadow-slate-200 border border-white">
                            <div class="flex justify-between items-center mb-6">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Grand Total</span>
                                <span class="text-3xl font-black text-[#e67e22]">Rs. {{ number_format($grandTotal, 2) }}</span>
                            </div>
                            <textarea class="w-full p-4 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold outline-none" wire:model="paymentNotes" placeholder="Transaction notes..." rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-auto pt-8 flex gap-3">
                        <button class="flex-1 py-4 bg-slate-100 text-slate-400 font-black rounded-xl uppercase tracking-widest hover:bg-slate-200 transition-all text-xs" wire:click="closePaymentModal">Cancel</button>
                        <button class="flex-[2] py-4 bg-[#e67e22] text-white font-black rounded-xl uppercase tracking-widest shadow-xl shadow-orange-500/20 text-xs flex items-center justify-center gap-2" wire:click="completeSaleWithPaymentAndPrint">
                            <span class="material-symbols-outlined">print_connect</span>
                            Process & Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- SALE PREVIEW MODAL (Invoice) --}}
        @if($showSaleModal && $createdSale)
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl h-[90vh] overflow-hidden relative transform transition-all flex flex-col">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
                <h3 class="font-black text-xs uppercase tracking-widest text-slate-400">Transaction Finalized</h3>
                <button class="text-slate-400 hover:text-slate-600" wire:click="closeModal"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="flex-1 overflow-y-auto p-12 custom-scrollbar bg-slate-100/50" id="printableInvoice">
                <div class="receipt-container bg-white shadow-2xl p-8 max-w-[800px] mx-auto rounded-lg relative overflow-hidden">
                    <style>
                        .receipt-container { width: 100%; max-width: 800px; margin: 0 auto; padding: 20px; }
                        .receipt-header { border-bottom: 3px solid #000; padding-bottom: 12px; margin-bottom: 12px; }
                        .receipt-row { display:flex; align-items:center; justify-content:space-between; }
                        .receipt-logo { flex: 0 0 150px; }
                        .receipt-center { flex: 1; text-align:center; }
                        .receipt-center h2 { margin: 0 0 4px 0; font-size: 2rem; letter-spacing: 2px; }
                        .receipt-right { flex: 0 0 150px; text-align:right; }
                        .mb-0 { margin-bottom: 0; }
                        .mb-1 { margin-bottom: 4px; }
                        table.receipt-table { width:100%; border-collapse: collapse; margin-top: 12px; }
                        table.receipt-table th{border-bottom: 1px solid #000; padding: 8px; text-align: left;}
                         table.receipt-table td { border: 0px solid #000; padding: 2px; text-align: left; }
                        table.receipt-table th { background: none; font-weight: bold; }
                        .text-end { text-align: right; }
                    </style>

                    <!-- Header -->
                    <div class="receipt-header">
                        <div class="receipt-row">
                            
                            <div class="receipt-center">
                                <h2 class="mb-0">HARDMEN (PVT) LTD</h2>
                                <p class="mb-0 text-muted" style="color:#666; font-size:12px;">TOOLS WITH POWER</p>
                                <p style="margin:0; text-align:center;"><strong> 421/2, Doolmala, thihariya, Kalagedihena.</strong></p>
                                <p style="margin:0; text-align:center;"><strong>TEL :</strong> (077) 9752950, <strong>EMAIL :</strong> Hardmenlanka@gmail.com</p>
                                <p style="margin:0; text-align:center; font-size:11px; margin-top:8px;"><strong></strong></p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice / Customer Details -->
                    <div style="display:flex; gap:20px; margin-bottom:12px; justify-content:space-between; align-items:flex-start;">
                        <div style="flex:0 0 45%; text-align:left;">
                            @if($createdSale->customer)
                            <p style="margin:0; font-size:12px;"><strong>Name:</strong> {{ $createdSale->customer->name }}</p>
                            <p style="margin:0; font-size:12px;"><strong>Phone:</strong> {{ $createdSale->customer->phone }}</p>
                            <p style="margin:0; font-size:12px;"><strong>Type:</strong> {{ ucfirst($createdSale->customer_type) }}</p>
                            @else
                            <p class="text-muted">Walk-in Customer</p>
                            @endif
                        </div>
                        <div style="flex:0 0 45%; text-align:right;">
                            <p style="margin:0; font-size:12px;"><strong>Invoice Number:</strong> {{ $createdSale->invoice_number }}</p>
                            <p style="margin:0; font-size:12px;"><strong>Date:</strong> {{ $createdSale->created_at->format('d/m/Y h:i A') }}</p>
                            <p style="margin:0; font-size:12px;"><strong>Payment Status:</strong> <span style="color:#e67e22; font-weight:bold;">{{ ucfirst($createdSale->payment_status ?? 'paid') }}</span></p>
                        </div>
                    </div>

                    <!-- Items -->
                    
                    <table class="receipt-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                
                                <th>Code</th>
                                <th>Item</th>
                                <th style="text-align:center;">Price</th>
                                <th style="text-align:center;">Qty</th>
                                <th style="text-align:center;">Discount</th>
                                <th style="text-align:center;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($createdSale->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->product_code ?? '' }}</td>
                                <td>{{ $item->product_name }}</td>
                                
                                <td class="text-end">Rs.{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">{{ $item->quantity }}</td>
                                <td class="text-end">
                                    @php
                                        $discountAmount = $item->discount_per_unit ?? 0;
                                    @endphp
                                    @if($item->discount_type === 'percentage' && $item->discount_percentage > 0)
                                        {{ number_format($item->discount_percentage, 0) }}% (Rs.{{ number_format($discountAmount * $item->quantity, 2) }})
                                    @elseif($discountAmount > 0)
                                        Rs.{{ number_format($discountAmount * $item->quantity, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">Rs.{{ number_format($item->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Summary / Payments -->
                    <div style="display:flex; gap:20px; margin-top:25px; border-top:2px solid #000; padding-top:12px;">
                        <div style="flex:1;">
                            <h4 style="margin:0 0 8px 0; color:#666;">PAYMENT INFORMATION</h4>
                            @if($createdSale->payments && $createdSale->payments->count() > 0)
                                @foreach($createdSale->payments as $payment)
                                <div style="margin-bottom:8px; padding:8px; border-left:3px solid {{ $payment->is_completed ? '#28a745' : '#ffc107' }}; background:#f8f9fa;">
                                    <p style="margin:0;"><strong>{{ $payment->is_completed ? 'Payment' : 'Scheduled Payment' }}:</strong> Rs.{{ number_format($payment->amount, 2) }}</p>
                                    <p style="margin:0;"><strong>Method:</strong> {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</p>
                                </div>
                                @endforeach
                            @else
                                <p class="text-muted">No payment information available</p>
                            @endif
                        </div>
                        <div style="flex:1;">
                            <div >
                                <h4 style="margin:0 0 8px 0; border-bottom:1px solid #000; padding-bottom:8px;">ORDER SUMMARY</h4>
                                @php
                                    // Calculate original subtotal (before any discounts)
                                    $originalSubtotal = $createdSale->items->sum(function($item) {
                                        return $item->unit_price * $item->quantity;
                                    });
                                    // Total discount = original subtotal - grand total
                                    $totalDiscountRs = $originalSubtotal - $createdSale->total_amount;
                                    // Calculate discount percentage
                                    $discountPercentage = $originalSubtotal > 0 ? ($totalDiscountRs / $originalSubtotal) * 100 : 0;
                                @endphp
                                <div style="display:flex; justify-content:space-between; margin-bottom:6px;"><span>Subtotal:</span><span>Rs.{{ number_format($originalSubtotal, 2) }}</span></div>
                                @if($totalDiscountRs > 0)
                                <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                                    <span>Discount:</span>
                                    <span>- Rs. {{ number_format($discountPercentage, 2) }}%</span>
                                </div>
                                @endif
                                <hr>
                                <div style="display:flex; justify-content:space-between;"><strong>Grand Total:</strong><strong>Rs.{{ number_format($createdSale->total_amount, 2) }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div style="margin-top:auto; text-align:center; padding-top:12px; display:flex; flex-direction:column;">
                        <div style="display:flex; justify-content:center; gap:20px; margin-bottom:12px;">
                            <div style="flex:0 0 50%; text-align:center;"><p><strong>....................</strong></p><p><strong>Authorized Signature</strong></p></div>
                            
                            <div style="flex:0 0 50%; text-align:center;"><p><strong>....................</strong></p><p><strong>Customer Signature</strong></p></div>
                        </div>
                        
                        <div>
                            <p style="margin:0; font-size:12px;">Thank you for your business!</p>
                            <p style="margin:0; font-size:12px; display:flex; align-items:center; justify-content:center; gap:12px;">
                                <span class="material-symbols-outlined" style="font-size:14px;"></span> www.hardmen.lk
                                <span class="material-symbols-outlined" style="font-size:14px;"></span> info@hardmen.lk
                            </p>
                            
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-center gap-3 shrink-0">
                <button class="px-8 py-3 bg-white border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 hover:bg-slate-50" wire:click="createNewSale">Close & New</button>
                <button class="px-10 py-3 bg-slate-800 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-slate-200 flex items-center gap-2" onclick="printInvoice()"><span class="material-symbols-outlined text-base">print</span> Print Invoice</button>
                <button class="px-8 py-3 bg-[#e67e22] text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-orange-500/20" wire:click="downloadInvoice">Download PDF</button>
            </div>
        </div>
        @endif
        
        {{-- POS REPORT MODAL (Close Register) --}}
        @if($showCloseRegisterModal)
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden relative transform transition-all border border-slate-100">
            <div class="bg-slate-900 p-8 text-center relative overflow-hidden">
                <div class="absolute inset-0 opacity-10 bg-[url('https://hardmen.lk/logo.png')] bg-center bg-no-repeat bg-contain scale-150"></div>
                <h3 class="text-xl font-black text-white uppercase tracking-[0.2em] relative z-10">Terminal Summary</h3>
                <p class="text-slate-400 text-[10px] font-bold mt-2 relative z-10">{{ date('d M Y | H:i') }}</p>
            </div>
            <div class="p-8 space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                        <label class="text-[8px] font-black text-slate-300 uppercase tracking-widest block mb-1">Session Inflow</label>
                        <span class="text-lg font-black text-slate-800">Rs. {{ number_format($sessionSummary['opening_cash'] ?? 0, 2) }}</span>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                        <label class="text-[8px] font-black text-slate-300 uppercase tracking-widest block mb-1">Total Turnover</label>
                        <span class="text-lg font-black text-[#e67e22]">Rs. {{ number_format($sessionSummary['total_pos_sales'] ?? 0, 2) }}</span>
                    </div>
                </div>
                <div class="space-y-3 pt-4 border-t border-slate-50">
                    <div class="flex justify-between items-center text-xs font-bold text-slate-500 py-1 transition-all hover:bg-slate-50 px-2 rounded">
                        <span>Terminal Cash Offset:</span>
                        <span class="text-slate-800">Rs. {{ number_format($sessionSummary['pos_cash_sales'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs font-bold text-slate-500 py-1 transition-all hover:bg-slate-50 px-2 rounded">
                        <span>Internal Expenses:</span>
                        <span class="text-red-500">Rs. {{ number_format($sessionSummary['expenses'] ?? 0, 2) }}</span>
                    </div>
                </div>
                <div class="p-6 bg-[#e67e22] rounded-2xl flex items-center justify-between text-white shadow-xl shadow-orange-500/20">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em]">Liquid Cash in Hand</span>
                    <span class="text-2xl font-black tracking-tighter">Rs. {{ number_format($sessionSummary['expected_cash'] ?? 0, 2) }}</span>
                </div>
                <div class="pt-2">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Managerial Notes</label>
                    <textarea class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl text-xs font-bold outline-none italic" rows="2" wire:model="closeRegisterNotes" placeholder="Log terminal anomalies..."></textarea>
                </div>
            </div>
            <div class="p-6 bg-slate-50 border-t border-slate-100 flex gap-3">
                <button class="flex-1 py-4 bg-white border border-slate-200 text-slate-400 font-black rounded-xl uppercase tracking-widest text-[10px] shadow-sm" wire:click="$set('showCloseRegisterModal', false)">Lock Review</button>
                <button class="flex-1 py-4 bg-slate-800 text-white font-black rounded-xl uppercase tracking-widest text-[10px] shadow-xl shadow-slate-200" wire:click="closeRegisterAndRedirect">Finalize Close</button>
            </div>
        </div>
        @endif
    </div>
    @endif

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('POS System Loaded');
        
        // Real-time Clock Implementation
        function updateClock() {
            const el = document.getElementById('posClock');
            if (!el) return;
            const now = new Date();
            el.innerText = now.getHours().toString().padStart(2, '0') + ':' + 
                          now.getMinutes().toString().padStart(2, '0') + ':' + 
                          now.getSeconds().toString().padStart(2, '0');
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Native Post-load adjustments
        const initLayout = () => {
            const grid = document.getElementById('productGridContainer');
            if(grid){ grid.style.scrollBehavior = 'smooth'; }
        };
        initLayout();

        // Keyboard Logic
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                if(@json(count($cart)) > 0){ @this.validateAndCreateSale(); }
            }
            if (e.key === 'F10') {
                e.preventDefault();
                @this.validateAndCreateSale();
            }
        });
    });

    // Print Invoice Function - Make it globally available
    function printInvoice() {
        console.log('=== Print Invoice Function Called ===');
        
        const printEl = document.getElementById('printableInvoice');
        if (!printEl) { 
            console.error('ERROR: Printable invoice element not found');
            setTimeout(function() {
                console.log('Retrying print after 1 second...');
                const retryEl = document.getElementById('printableInvoice');
                if (retryEl) {
                    printInvoice();
                } else {
                    alert('Invoice not ready for printing. Please use the Print Invoice button.');
                }
            }, 1000);
            return; 
        }

        console.log('Print element found:', printEl);

        // Get the actual receipt container
        const receiptContainer = printEl.querySelector('.receipt-container');
        if (!receiptContainer) {
            console.error('ERROR: Receipt container not found inside printableInvoice');
            alert('Invoice content not ready. Please try again.');
            return;
        }

        console.log('Receipt container found, preparing content...');

        // Clone the content to avoid modifying the original
        let content = receiptContainer.cloneNode(true);
        
        // Remove any buttons or interactive elements from print
        content.querySelectorAll('button, .no-print').forEach(el => el.remove());

        // Ensure footer is anchored to bottom: add a class and inline style to footer block
        const footerEl = content.querySelector('div[style*="border-top:2px solid #000"]') || content.querySelector('div:last-child');
        if (footerEl) {
            footerEl.classList.add('receipt-footer');
            // Use auto margin so it pushes to bottom inside the flex layout
            footerEl.style.marginTop = 'auto';
        }

        // Get the HTML string
        let htmlContent = content.outerHTML;

        console.log('Content prepared, opening print window...');

        // Open a new window
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        
        if (!printWindow) {
            console.error('ERROR: Print window blocked by popup blocker');
            alert('Popup blocked. Please allow pop-ups for this site or use the Print Invoice button below.');
            return;
        }

        console.log('Print window opened successfully');

        // Complete HTML document with styles
        const fullHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Invoice - HARDMEN (PVT) LTD</title>
                <style>
                    @page { 
                        size: letter portrait; 
                        margin: 6mm; 
                    }

                    html, body { height: 100%; }

                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }

                    body { 
                        font-family: sans-serif; 
                        color: #000; 
                        background: #fff; 
                        padding: 10mm;
                        font-size: 12px;
                        line-height: 1.4;
                    }

                    .receipt-container { 
                        max-width: 800px; 
                        margin: 0 auto;
                        padding: 20px;
                        background: white;
                        display: flex;
                        flex-direction: column;
                        min-height: 100vh;
                        page-break-inside: avoid;
                    }

                    .receipt-footer { 
                        margin-top: auto !important; 
                        page-break-inside: avoid;
                    }
                    
                    .receipt-header { 
                        border-bottom: 3px solid #000; 
                        padding-bottom: 12px; 
                        margin-bottom: 12px; 
                    }
                    
                    .receipt-row { 
                        display: flex; 
                        align-items: center; 
                        justify-content: space-between; 
                    }
                    
                    .receipt-center { 
                        flex: 1; 
                        text-align: center; 
                    }
                    
                    .receipt-center h2 { 
                        margin: 0 0 4px 0; 
                        font-size: 2rem; 
                        letter-spacing: 2px;
                        font-weight: bold;
                    }
                    
                    table.receipt-table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin-top: 12px; 
                    }
                    
                    table.receipt-table th {
                        border-bottom: 1px solid #000; 
                        padding: 8px; 
                        text-align: left;
                        font-weight: bold;
                        background: none;
                    }
                    
                    table.receipt-table td { 
                        padding: 2px; 
                        text-align: left;
                        border: none;
                    }
                    
                    .text-end { 
                        text-align: right; 
                    }
                    
                    .text-muted {
                        color: #000000;
                    }
                    
                    p {
                        margin: 4px 0;
                    }
                    
                    strong {
                        font-weight: bold;
                    }
                    
                    hr {
                        border: none;
                        border-top: 1px solid #000;
                        margin: 8px 0;
                    }
                    
                    @media print {
                        body {
                            padding: 0;
                        }
                        
                        .receipt-container {
                            box-shadow: none !important;
                        }
                        
                        .receipt-container {
                            page-break-inside: avoid;
                        }
                    }
                </style>
            </head>
            <body>
                ${htmlContent}
                <script>
                    console.log('Print window document loaded');
                    window.onload = function() {
                        console.log('Print window fully loaded, triggering print dialog...');
                        setTimeout(function() {
                            try {
                                window.print();
                                console.log('Print dialog triggered');
                            } catch(e) {
                                console.error('Print failed:', e);
                                alert('Print failed: ' + e.message);
                            }
                        }, 500);
                    };
                <\/script>
            </body>
            </html>
        `;

        // Write the content
        try {
            printWindow.document.open();
            printWindow.document.write(fullHtml);
            printWindow.document.close();
            console.log('=== Content written to print window successfully ===');
        } catch(e) {
            console.error('ERROR writing to print window:', e);
            alert('Failed to prepare print: ' + e.message);
        }
        
        // Focus the print window
        printWindow.focus();
    }

    // Make printInvoice available globally
    window.printInvoice = printInvoice;
    console.log('printInvoice function registered globally');
</script>
</div>