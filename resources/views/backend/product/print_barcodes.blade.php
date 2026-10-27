@extends('layout.main')
@section('title') {{ 'Print Product Barcodes' }} @endsection

@section('styles')
<style>
    .labels-page {
        background: #fff;
        padding: 12px;
    }

    .labels-toolbar {
        margin-bottom: 12px;
        display: flex !important;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: #fff;
        position: relative;
        z-index: 1;
        padding: 8px 0;
    }

    .labels-actions {
        display: flex !important;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .labels-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .label-card {
        border: 1px dashed #bbb;
        border-radius: 6px;
        padding: 10px;
        margin-top: 16px;
        min-height: 140px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background: #fff;
    }

    .label-name {
        font-weight: 600;
        font-size: 12px;
        line-height: 1.2;
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .barcode-wrap {
        text-align: center;
        margin-bottom: 4px;
    }

    .barcode-number {
        text-align: center;
        letter-spacing: 1px;
        font-size: 11px;
        font-family: monospace;
    }

    @media print {
        .labels-toolbar,
        .labels-actions,
        .main-footer,
        .vertical-menu,
        .topbar,
        .page-title-box {
            display: none !important;
        }

        .main-content,
        .page-content,
        .container-fluid,
        .labels-page {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        .labels-grid {
            gap: 6px;
        }

        .label-card {
            page-break-inside: avoid;
        }
    }
</style>
@endsection

@section('content')
<div class="page-content labels-page">
    <div class="container-fluid">
        <div class="labels-toolbar">
            <h4 class="mb-0">Print Product Barcode Labels</h4>
            <div class="labels-actions">
                <button class="btn btn-dark" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="{{ route('products.index') }}" class="btn btn-outline-dark">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="labels-grid">
            @foreach($products as $product)
                <div class="label-card">
                    <div>
                        <div class="label-name" title="{{ $product->name }}">{{ $product->name }}</div>
                    </div>

                    <div class="barcode-wrap">
                        <svg class="barcode-svg"
                             jsbarcode-format="EAN13"
                             jsbarcode-value="{{ $product->barcode }}"
                             jsbarcode-textmargin="2"
                             jsbarcode-fontsize="12"
                             jsbarcode-height="40"
                             jsbarcode-width="1.2"
                             jsbarcode-margin="0">
                        </svg>
                        <div class="barcode-number">{{ $product->barcode }}</div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/jsbarcode/JsBarcode.all-3.11.6.min.js') }}"></script>
<script>
    (function () {
        if (typeof JsBarcode === 'undefined') {
            if (window.Notiflix && Notiflix.Notify) {
                Notiflix.Notify.failure('Barcode renderer failed to load. Check your internet connection and refresh.');
            } else {
                alert('Barcode renderer failed to load. Check your internet connection and refresh.');
            }
            return;
        }

        var barcodes = document.querySelectorAll('.barcode-svg');
        barcodes.forEach(function (el) {
            try {
                JsBarcode(el).init();
            } catch (e) {
                el.outerHTML = '<div style="color:#dc3545;font-size:11px;">Invalid barcode value</div>';
            }
        });
    })();
</script>
@endsection
