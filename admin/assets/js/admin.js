/**
 * KnowTech Subscriptions - Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Get subscription details via AJAX
    $(document).on('click', '.kt-view-details', function(e) {
        e.preventDefault();
        
        var subId = $(this).data('id');
        var $modal = $('#kt-subscription-modal');
        var $details = $('#kt-subscription-details');
        
        $details.html('<p style="text-align:center;"><span class="spinner is-active"></span> در حال بارگذاری...</p>');
        $modal.fadeIn();
        
        $.ajax({
            url: ktAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'kt_get_subscription_details',
                sub_id: subId,
                nonce: ktAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $details.html(response.data.html);
                } else {
                    $details.html('<p style="color:red;">خطا در بارگذاری اطلاعات</p>');
                }
            },
            error: function() {
                $details.html('<p style="color:red;">خطا در ارتباط با سرور</p>');
            }
        });
    });
    
    // Close modal
    $(document).on('click', '.kt-modal-close, .kt-modal', function(e) {
        if (e.target === this) {
            $(this).closest('.kt-modal').fadeOut();
        }
    });
    
    // Escape key to close modal
    $(document).keyup(function(e) {
        if (e.key === "Escape") {
            $('.kt-modal').fadeOut();
        }
    });
    
    // Confirm delete
    $('.button-link-delete').on('click', function(e) {
        if (!confirm('آیا مطمئن هستید که می‌خواهید این مورد را حذف کنید؟')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Product form - toggle fields based on login method
    $('#login_method').on('change', function() {
        var method = $(this).val();
        
        $('#form-login-fields').hide();
        $('#cookie-login-fields').hide();
        
        if (method === 'form') {
            $('#form-login-fields').slideDown();
        } else if (method === 'cookie') {
            $('#cookie-login-fields').slideDown();
        }
    }).trigger('change');
    
    // Auto-save reminder
    var formChanged = false;
    $('form input, form textarea, form select').on('change', function() {
        formChanged = true;
    });
    
    $(window).on('beforeunload', function() {
        if (formChanged) {
            return 'تغییرات ذخیره نشده‌اند. آیا مطمئن هستید؟';
        }
    });
    
    $('form').on('submit', function() {
        formChanged = false;
    });
});
