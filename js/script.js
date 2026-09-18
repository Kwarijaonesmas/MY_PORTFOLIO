// Custom Client-side Scripts for Bushenyi Hostel Booking & Management System

document.addEventListener('DOMContentLoaded', function () {
    // 1. Dynamic Price Slider Sync
    const priceRange = document.getElementById('price_range');
    const priceValueDisplay = document.getElementById('price_value_display');

    if (priceRange && priceValueDisplay) {
        priceRange.addEventListener('input', function () {
            const formattedPrice = parseInt(this.value).toLocaleString('en-UG');
            priceValueDisplay.textContent = 'UGX ' + formattedPrice;
        });
    }

    // 2. MTN Mobile Money & Airtel Money Payment Handlers
    const momoForm = document.getElementById('momoPaymentForm');
    if (momoForm) {
        momoForm.addEventListener('submit', function (e) {
            const phoneInput = document.getElementById('payment_phone');
            const pinInput = document.getElementById('momo_pin');

            if (phoneInput && !/^07[7869]\d{7}$/.test(phoneInput.value.replace(/\s+/g, ''))) {
                e.preventDefault();
                alert('Please enter a valid MTN Mobile Money Uganda number (e.g., 0771234567, 0781234567, 0761234567).');
                phoneInput.focus();
                return false;
            }

            if (pinInput && pinInput.value.length !== 4) {
                e.preventDefault();
                alert('Please enter a valid 4-digit MoMo PIN.');
                pinInput.focus();
                return false;
            }

            const payBtn = document.getElementById('paySubmitBtn');
            if (payBtn) {
                payBtn.disabled = true;
                payBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Processing MTN MoMo Payment...';
            }
        });
    }

    const airtelForm = document.getElementById('airtelPaymentForm');
    if (airtelForm) {
        airtelForm.addEventListener('submit', function (e) {
            const phoneInput = document.getElementById('airtel_phone');
            const pinInput = document.getElementById('airtel_pin');

            if (phoneInput && !/^07[054]\d{7}$/.test(phoneInput.value.replace(/\s+/g, ''))) {
                e.preventDefault();
                alert('Please enter a valid Airtel Money Uganda number (e.g., 0701234567, 0751234567, 0741234567).');
                phoneInput.focus();
                return false;
            }

            if (pinInput && pinInput.value.length !== 4) {
                e.preventDefault();
                alert('Please enter a valid 4-digit Airtel Money PIN.');
                pinInput.focus();
                return false;
            }

            const payBtn = document.getElementById('airtelPaySubmitBtn');
            if (payBtn) {
                payBtn.disabled = true;
                payBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Processing Airtel Money Payment...';
            }
        });
    }

    // 3. Confirm action helper
    const deleteButtons = document.querySelectorAll('.confirm-action');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});
