<div class="product-details">
    @php
        $quantity = (float) ($product->quantity ?? 0);
        $stockLevel = (float) ($product->stock_level ?? 0);
        $costPrice = (float) ($product->cost_price ?? 0);
        $sellingPrice = (float) ($product->selling_price ?? 0);
    @endphp

    <div class="row">
        <div class="col-md-12">
            <h4>{{ $product->name }}</h4>
            <p class="text-muted">
                <strong>Barcode:</strong> {{ $product->barcode ?? 'N/A' }}
            </p>
            
            <hr>
            
            <div class="product-info">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="40%"><strong>Category:</strong></td>
                        <td>{{ $product->category->name ?? 'Uncategorized' }}</td>
                    </tr>
                    @if($product->unit)
                    <tr>
                        <td><strong>Unit:</strong></td>
                        <td>{{ $product->unit->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Cost Price:</strong></td>
                        <td><span class="font-weight-bold">₵{{ number_format($costPrice, 2) }}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Selling Price:</strong></td>
                        <td><span class="font-weight-bold">₵{{ number_format($sellingPrice, 2) }}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Stock Quantity:</strong></td>
                        <td>
                            @if($quantity <= 0)
                                <span class="badge badge-danger">Out of Stock</span>
                            @elseif($quantity <= $stockLevel)
                                <span class="badge badge-warning">Low Stock ({{ number_format($quantity, 0) }})</span>
                            @else
                                <span class="badge badge-success">{{ number_format($quantity, 0) }} units</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            
            @if($product->tags)
            <hr>
            <div class="product-tags">
                <strong>Tags:</strong>
                <div class="mt-1">
                    @foreach(explode(',', $product->tags) as $tag)
                        <span class="badge badge-secondary">{{ trim($tag) }}</span>
                    @endforeach
                </div>
            </div>
            @endif
            
            <hr>
            <div class="text-muted small">
                <div><i class="fas fa-calendar-plus"></i> Created: {{ $product->created_at->format('d M, Y h:i A') }}</div>
                <div><i class="fas fa-calendar-edit"></i> Last Updated: {{ $product->updated_at->format('d M, Y h:i A') }}</div>
            </div>
        </div>
    </div>
</div>

<style>
    .product-details {
        padding: 10px;
    }
    .product-info table {
        margin-bottom: 0;
    }
    .product-info td {
        padding: 5px 0;
    }
    .product-description {
        max-height: 200px;
        overflow-y: auto;
    }
</style>

<script>
    // Optional: Add to cart from modal
    function addToCart(productId) {
        // Your add to cart logic here
        console.log('Add to cart:', productId);
        Notiflix.Notify.info('Add to cart feature coming soon');
    }
</script>