@extends('layout.main')
@section('title') Delivery Verification @endsection

@section('styles')
<style>
    .page-content.delivery-verify-page {
        --delivery-teal: #087f8c;
        --delivery-teal-dark: #066b76;
        padding: 102px 16px 32px;
    }

    .delivery-verify-card {
        width: min(100%, 680px);
        margin: 0 auto;
        padding: 36px 32px 32px;
        border: 1px solid #dce3e9;
        border-top: 4px solid var(--delivery-teal);
        border-radius: 7px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(18, 38, 63, 0.09);
        text-align: center;
    }

    .delivery-verify-mark {
        display: inline-flex;
        width: 56px;
        height: 56px;
        margin-bottom: 14px;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #e7f5f5;
        color: var(--delivery-teal);
        font-size: 26px;
        line-height: 1;
    }

    .delivery-verify-title {
        margin: 0;
        color: #142d4e;
        font-size: 28px;
        font-weight: 500;
        line-height: 1.2;
    }

    .delivery-verify-subtitle {
        margin: 12px 0 0;
        color: #64748b;
        font-size: 16px;
        line-height: 1.45;
    }

    .delivery-verify-intro {
        margin-bottom: 38px;
    }

    .delivery-entry-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 56px 128px;
        gap: 10px;
        align-items: stretch;
    }

    .delivery-code-input,
    .delivery-action-button {
        min-width: 0;
        height: 56px;
        border-radius: 7px;
    }

    .delivery-code-input {
        border: 1px solid #aebbc8;
        color: #243b53;
        font-size: 16px;
        text-align: center;
        text-transform: uppercase;
    }

    .delivery-code-input::placeholder {
        color: #6b7280;
        opacity: 1;
        text-transform: none;
    }

    .delivery-code-input:focus {
        border-color: var(--delivery-teal);
        box-shadow: 0 0 0 4px rgba(8, 127, 140, 0.16);
    }

    .delivery-action-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--delivery-teal);
        background: var(--delivery-teal);
        color: #fff;
        font-size: 15px;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .delivery-action-button:hover,
    .delivery-action-button:focus-visible {
        border-color: var(--delivery-teal-dark);
        background: var(--delivery-teal-dark);
        color: #fff;
    }

    .delivery-action-button:disabled {
        cursor: wait;
        opacity: 0.7;
    }

    .delivery-camera-button {
        font-size: 17px;
    }

    .delivery-stop-button {
        display: inline-flex;
        width: min(100%, 220px);
        height: 46px;
        margin: 16px auto 0;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: 1px solid #dc3545;
        border-radius: 7px;
        color: #b4232f;
        font-weight: 600;
    }

    .delivery-stop-button:hover,
    .delivery-stop-button:focus-visible {
        border-color: #b4232f;
        background: #b4232f;
        color: #fff;
    }

    .delivery-stop-button[hidden] {
        display: none;
    }

    .delivery-camera-preview {
        display: block;
        width: min(100%, 480px);
        max-height: 300px;
        margin: 14px auto 0;
        border-radius: 6px;
        background: #111;
    }

    .delivery-result:empty {
        display: none;
    }

    .delivery-result {
        margin: 20px 0 0;
        text-align: left;
    }

    @media (max-width: 991.98px) {
        .page-content.delivery-verify-page {
            padding-top: 28px;
        }
    }

    @media (max-width: 640px) {
        .page-content.delivery-verify-page {
            padding: 24px 10px 28px;
        }

        .delivery-verify-card {
            padding: 26px 18px 24px;
        }

        .delivery-verify-title {
            font-size: 26px;
        }

        .delivery-verify-subtitle {
            font-size: 15px;
        }

        .delivery-verify-intro {
            margin-bottom: 28px;
        }

        .delivery-entry-row {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 14px 10px;
        }

        .delivery-code-input {
            grid-column: 1 / -1;
            margin-bottom: 4px;
        }

        .delivery-code-input,
        .delivery-action-button {
            height: 52px;
        }

        .delivery-code-input {
            font-size: 16px;
        }

        .delivery-action-button {
            font-size: 15px;
            font-weight: 600;
        }

        .delivery-camera-button {
            font-size: 17px;
        }
    }

    @media (max-width: 360px) {
        .delivery-entry-row {
            gap: 12px 8px;
        }

        .delivery-code-input {
            font-size: 15px;
        }
    }
</style>
@endsection

@section('content')
<div class="page-content delivery-verify-page">
    <main class="delivery-verify-card">
        <div class="delivery-verify-intro">
            <div class="delivery-verify-mark" aria-hidden="true"><i class="fas fa-barcode"></i></div>
            <h1 class="delivery-verify-title">Delivery Verification</h1>
            <p class="delivery-verify-subtitle">Scan the receipt barcode or enter the code manually.</p>
        </div>

        <form id="deliveryVerifyForm" action="{{ route('sales.delivery.verify') }}" method="POST">
            @csrf
            <label for="delivery_code" class="visually-hidden">Delivery code</label>
            <div class="delivery-entry-row">
                <input type="text" id="delivery_code" name="code" class="form-control delivery-code-input" placeholder="Scan code or enter manually" autocomplete="off" autofocus required>
                <button type="button" id="startDeliveryCamera" class="delivery-action-button delivery-camera-button" title="Start camera scanner" aria-label="Start camera scanner">
                    <i class="fas fa-camera" aria-hidden="true"></i>
                </button>
                <button type="submit" id="verifyDeliveryButton" class="delivery-action-button">
                    <i class="fas fa-check me-1" aria-hidden="true"></i> Verify
                </button>
            </div>
            <button type="button" id="stopDeliveryCamera" class="delivery-stop-button" hidden>
                <i class="fas fa-stop-circle" aria-hidden="true"></i> Stop Camera
            </button>
        </form>

        <video id="deliveryCameraPreview" autoplay playsinline muted class="delivery-camera-preview" hidden></video>
        <div id="deliveryVerifyResult" class="delivery-result" role="status" aria-live="polite"></div>
    </main>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/zxing/zxing-library-0.21.3.min.js') }}"></script>
<script>
    (function () {
        const form = document.getElementById('deliveryVerifyForm');
        const input = document.getElementById('delivery_code');
        const result = document.getElementById('deliveryVerifyResult');
        const submitButton = document.getElementById('verifyDeliveryButton');
        const video = document.getElementById('deliveryCameraPreview');
        const startCameraButton = document.getElementById('startDeliveryCamera');
        const stopCameraButton = document.getElementById('stopDeliveryCamera');
        let reader = null;
        let cameraControls = null;

        function stopCamera() {
            if (cameraControls && typeof cameraControls.stop === 'function') cameraControls.stop();
            cameraControls = null;
            if (reader && typeof reader.reset === 'function') reader.reset();
            reader = null;
            if (video.srcObject) video.srcObject.getTracks().forEach(function (track) { track.stop(); });
            video.srcObject = null;
            video.hidden = true;
            stopCameraButton.hidden = true;
            startCameraButton.disabled = false;
            startCameraButton.setAttribute('aria-pressed', 'false');
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            const code = input.value.trim();
            if (!code || submitButton.disabled) return;

            submitButton.disabled = true;
            result.textContent = '';
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });
                const payload = await response.json();
                const success = payload.status === 'success';
                const duplicate = payload.status === 'duplicate';
                result.className = 'delivery-result alert ' + (success ? 'alert-success' : (duplicate ? 'alert-warning' : 'alert-danger'));
                result.textContent = payload.message || 'Unable to verify delivery.';
                if (success || duplicate) input.value = '';
            } catch (error) {
                result.className = 'delivery-result alert alert-danger';
                result.textContent = 'Unable to reach the server. Check the connection and try again.';
            } finally {
                submitButton.disabled = false;
                input.focus();
            }
        });

        startCameraButton.addEventListener('click', function () {
            if (!window.ZXing || !window.ZXing.BrowserMultiFormatReader) {
                result.className = 'delivery-result alert alert-warning';
                result.textContent = 'Camera scanning is unavailable. Enter or scan the code into the text field.';
                return;
            }
            if (!(window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
                result.className = 'delivery-result alert alert-warning';
                result.textContent = 'Camera scanning requires HTTPS. Manual entry remains available.';
                return;
            }

            reader = new window.ZXing.BrowserMultiFormatReader();
            video.hidden = false;
            startCameraButton.disabled = true;
            startCameraButton.setAttribute('aria-pressed', 'true');
            stopCameraButton.hidden = false;
            reader.decodeFromVideoDevice(null, video, function (barcode, error, controls) {
                if (controls) cameraControls = controls;
                if (barcode) {
                    input.value = barcode.getText();
                    stopCamera();
                    form.requestSubmit();
                }
            });
        });

        stopCameraButton.addEventListener('click', stopCamera);
        window.addEventListener('beforeunload', stopCamera);
    })();
</script>
@endsection