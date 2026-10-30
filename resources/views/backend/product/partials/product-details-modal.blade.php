@php
    $quantity = (float) ($product->quantity ?? 0);
    $stockLevel = (float) ($product->stock_level ?? 1);
    $costPrice = (float) ($product->cost_price ?? 0);
    $sellingPrice = (float) ($product->selling_price ?? 0);
    $margin = $costPrice > 0 ? (($sellingPrice - $costPrice) / $costPrice) * 100 : 0;
    $user = auth()->user();
    $userOutletId = $user ? $user->outlet_id : null;
@endphp

<div class="product-modal-container">
    {{-- Header Banner Card --}}
    <div class="product-hero-card mb-3 p-3 rounded-3" style="background: linear-gradient(135deg, #f0fdfa 0%, #e6f4f2 100%); border: 1px solid #ccfbf1;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1 font-weight-bold" style="color: #034141;">
                    <i class="fas fa-box text-teal mr-2 me-1" style="color: #0F766E;"></i>
                    {{ $product->name }}
                </h4>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <span class="badge" style="background: #0F766E; color: #fff; font-size: 11px; padding: 5px 9px;">
                        <i class="fas fa-sitemap me-1"></i> {{ $product->category->name ?? 'Uncategorized' }}
                    </span>
                    @if($product->unit)
                        <span class="badge" style="background: #034141; color: #fff; font-size: 11px; padding: 5px 9px;">
                            <i class="fas fa-balance-scale me-1"></i> {{ $product->unit->name }}
                        </span>
                    @endif
                    <span class="badge bg-white text-dark border" style="font-size: 11px; padding: 5px 9px; font-family: monospace;">
                        <i class="fas fa-barcode text-muted me-1"></i> {{ $product->barcode ?: 'No Barcode' }}
                    </span>
                    <span class="badge bg-info text-white" style="font-size: 11px; padding: 5px 9px;">
                        <i class="fas fa-globe me-1"></i> Master Catalog
                    </span>
                </div>
            </div>
            <div class="text-end text-right">
                @if($quantity <= 0)
                    <span class="badge bg-danger text-white px-3 py-2" style="font-size: 13px; border-radius: 20px;">
                        <i class="fas fa-times-circle me-1"></i> Out of Stock
                    </span>
                @elseif($quantity <= $stockLevel)
                    <span class="badge bg-warning text-dark px-3 py-2" style="font-size: 13px; border-radius: 20px;">
                        <i class="fas fa-exclamation-triangle me-1"></i> Low Stock
                    </span>
                @else
                    <span class="badge bg-success text-white px-3 py-2" style="font-size: 13px; border-radius: 20px; background-color: #10B981 !important;">
                        <i class="fas fa-check-circle me-1"></i> In Stock
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- 4 Metric Summary Cards --}}
    <div class="row g-2 mb-3">
        {{-- Cost Price --}}
        <div class="col-md-3 col-6">
            <div class="kpi-card p-2 text-center rounded border bg-light h-100">
                <div class="text-muted small mb-1"><i class="fas fa-coins me-1 text-secondary"></i> Cost Price</div>
                <div class="h5 mb-0 font-weight-bold" style="color: #475569;">
                    ₵{{ number_format($costPrice, 2) }}
                </div>
                <small class="text-muted" style="font-size: 10px;">Central Procurement</small>
            </div>
        </div>

        {{-- Selling Price --}}
        <div class="col-md-3 col-6">
            <div class="kpi-card p-2 text-center rounded border bg-light h-100">
                <div class="text-muted small mb-1"><i class="fas fa-tag me-1" style="color: #0F766E;"></i> Selling Price</div>
                <div class="h5 mb-0 font-weight-bold" style="color: #034141;">
                    ₵{{ number_format($sellingPrice, 2) }}
                </div>
                @if($margin > 0)
                    <small class="text-success font-weight-bold" style="font-size: 10px;">+{{ number_format($margin, 1) }}% markup</small>
                @else
                    <small class="text-muted" style="font-size: 10px;">Retail Standard</small>
                @endif
            </div>
        </div>

        {{-- Total Stock --}}
        <div class="col-md-3 col-6">
            <div class="kpi-card p-2 text-center rounded border h-100" style="background: #f8fafc;">
                <div class="text-muted small mb-1"><i class="fas fa-cubes me-1 text-primary"></i> Total Stock</div>
                <div class="h5 mb-0 font-weight-bold text-dark">
                    {{ number_format($quantity, 0) }} <small class="text-muted" style="font-size:12px;">units</small>
                </div>
                <small class="text-muted" style="font-size: 10px;">Across All Stations</small>
            </div>
        </div>

        {{-- Restock Threshold --}}
        <div class="col-md-3 col-6">
            <div class="kpi-card p-2 text-center rounded border bg-light h-100">
                <div class="text-muted small mb-1"><i class="fas fa-bell me-1 text-warning"></i> Reorder Point</div>
                <div class="h5 mb-0 font-weight-bold text-dark">
                    {{ number_format($stockLevel, 0) }} <small class="text-muted" style="font-size:12px;">units</small>
                </div>
                <small class="text-muted" style="font-size: 10px;">Threshold Alert</small>
            </div>
        </div>
    </div>

    {{-- Stock by Location Breakdown Table --}}
    @if(isset($outlets) && $outlets->count() > 0)
        <div class="card border mb-3" style="border-radius: 10px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
            <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center" style="background: #034141; color: #fff;">
                <span class="font-weight-bold" style="font-size: 13px;">
                    <i class="fas fa-warehouse mr-1 me-1"></i> Inventory Distribution Across Outlets
                </span>
                <span class="badge bg-light text-dark" style="font-size: 11px;">
                    {{ $outlets->count() }} Stations
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead style="background: #f1f5f9; font-size: 11px; text-transform: uppercase; color: #475569;">
                        <tr>
                            <th class="ps-3" style="padding-left: 14px;">Outlet Station</th>
                            <th>Station Scope</th>
                            <th class="text-center">Reorder Point</th>
                            <th class="text-end text-right">On-Hand Stock</th>
                            <th class="text-center pe-3" style="padding-right: 14px;">Availability</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 12.5px;">
                        @foreach($outlets as $outlet)
                            @php
                                $oStock = $product->outletStocks ? $product->outletStocks->firstWhere('outlet_id', $outlet->id) : null;
                                $oQty = (float) ($oStock ? $oStock->quantity : 0);
                                $oThreshold = (float) ($oStock ? $oStock->stock_level : 1);
                                $isCurrentStation = ($userOutletId && (int)$userOutletId === (int)$outlet->id);
                            @endphp
                            <tr style="{{ $isCurrentStation ? 'background-color: #ecfdf5; font-weight: 500;' : '' }}">
                                <td class="ps-3" style="padding-left: 14px;">
                                    <strong>{{ $outlet->name }}</strong>
                                    @if($isCurrentStation)
                                        <span class="badge" style="background: #0F766E; color:#fff; font-size: 10px; margin-left: 4px;">
                                            <i class="fas fa-map-marker-alt me-1"></i> Your Station
                                        </span>
                                    @endif
                                    @if(!empty($outlet->location))
                                        <br><small class="text-muted" style="font-size: 10px;">{{ $outlet->location }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($outlet->is_central)
                                        <span class="badge bg-primary text-white" style="font-size: 10px;">
                                            <i class="fas fa-building me-1"></i> Central Warehouse
                                        </span>
                                    @else
                                        <span class="badge bg-secondary text-white" style="font-size: 10px;">
                                            <i class="fas fa-store me-1"></i> Retail Outlet
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center text-muted">
                                    {{ number_format($oThreshold, 0) }}
                                </td>
                                <td class="text-end text-right font-weight-bold" style="font-size: 13px;">
                                    {{ number_format($oQty, 0) }} <span class="text-muted font-weight-normal" style="font-size: 11px;">units</span>
                                </td>
                                <td class="text-center pe-3" style="padding-right: 14px;">
                                    @if($oQty <= 0)
                                        <span class="badge bg-danger text-white px-2 py-1" style="font-size: 10.5px;">Out of Stock</span>
                                    @elseif($oQty <= $oThreshold)
                                        <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 10.5px;">Low Stock</span>
                                    @else
                                        <span class="badge bg-success text-white px-2 py-1" style="font-size: 10.5px; background-color: #10B981 !important;">Available</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Tags Section if present --}}
    @if(!empty($product->tags))
        <div class="mb-3 p-2 rounded bg-light border">
            <small class="text-muted font-weight-bold d-block mb-1"><i class="fas fa-tags me-1"></i> Product Tags:</small>
            <div class="d-flex flex-wrap gap-1">
                @foreach(explode(',', $product->tags) as $tag)
                    @if(trim($tag) !== '')
                        <span class="badge bg-white text-dark border" style="font-size: 11px;">{{ trim($tag) }}</span>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Audit Metadata Footer --}}
    <div class="d-flex justify-content-between align-items-center text-muted pt-2 border-top" style="font-size: 11px;">
        <div>
            <i class="fas fa-calendar-alt me-1"></i> Created: <strong>{{ $product->created_at ? $product->created_at->format('d M, Y h:i A') : 'N/A' }}</strong>
        </div>
        <div>
            <i class="fas fa-clock me-1"></i> Last Updated: <strong>{{ $product->updated_at ? $product->updated_at->format('d M, Y h:i A') : 'N/A' }}</strong>
        </div>
    </div>
</div>

<style>
    .kpi-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.06);
    }
</style>
