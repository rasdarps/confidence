let _token = $('meta[name="csrf-token"]').attr('content');


function isNumberKey(evt){
    var charCode = (evt.which) ? evt.which : evt.keyCode
    if (charCode > 31 && (charCode < 48 || charCode > 57))
        return false;
    return true;
 }

function isCharKey(evt){

    
    var keyunicode=event.charCode || event.keyCode 
    return (keyunicode>=65 && keyunicode<=122 || keyunicode==8 || keyunicode==32)? true : false 
}


function showLoadingNotiflix(element, message = 'Processing Please wait...'){
    Notiflix.Block.circle('.'+element, message ,{
        svgSize: '90px',
        svgColor: 'rgb(248, 121, 108)',
        messageFontSize: '16px',
        messageMaxLength: 50,
    });
    
}

function showPulseLoadingNotiflix(element){
    
    Notiflix.Block.pulse('.'+element,{
        svgColor: '#5567FF',
        backgroundColor: 'rgba(255,255,255)',
    } );
    
}

function showLoadingNotiflixHourGlass(element, message = 'Processing. Please wait...'){
    Notiflix.Block.hourglass('.'+element, message, {
        svgSize: '90px',
        svgColor: '#5567FF',
        messageFontSize: '16px',
        messageMaxLength: 50,
    });
    
}

function hideLoadingNotiflix(element){
    Notiflix.Block.remove('.'+element);
}

function ajax(route, data = {}, options = {}) {
    // Default options
    const settings = {
        element: 'card_content',           // Loading element ID
        showLoading: true,                 // Show loading indicator
        showGlobalError: true,             // Show global error alert
        clearErrors: true,                 // Clear previous errors
        scrollToError: true,               // Scroll to first error
        errorSelector: '.error-text',      // Error message selector
        inputErrorClass: 'is-invalid',     // Class for error styling
        beforeSend: null,                  // Custom beforeSend callback
        complete: null,                    // Custom complete callback
        success: null,                     // Success callback
        error: null,                       // Custom error callback
        ...options
    };
    
    return $.ajax({
        url: route,
        data: data,
        type: 'POST',
        processData: false,
        contentType: false,
        beforeSend: function (xhr) {
            if (settings.showLoading) {
                showLoadingNotiflix(settings.element);
            }
            
            // Disable submit buttons in the form if data is FormData
            if (data instanceof FormData && data.get('_method') !== 'DELETE') {
                $(settings.element).find('button[type="submit"]').prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
            }
            
            // Custom beforeSend callback
            if (settings.beforeSend) settings.beforeSend(xhr);
        },
        complete: function (xhr, status) {
            if (settings.showLoading) {
                hideLoadingNotiflix(settings.element);
            }
            
            // Re-enable submit buttons
            $(settings.element).find('button[type="submit"]').prop('disabled', false)
                .html('Submit');
            
            // Custom complete callback
            if (settings.complete) settings.complete(xhr, status);
        },
        error: function (xhr, status, error) {
            // Handle validation errors automatically
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                handleValidationErrors(xhr.responseJSON.errors, settings);
            } else {
                // Handle other errors
                handleGeneralError(xhr, settings);
            }
            
            // Custom error callback
            if (settings.error) settings.error(xhr, status, error);
        },
        dataType: 'json',
        success: function(response) {
            // Auto-clear errors on success
            if (settings.clearErrors) {
                clearAllErrors(settings);
            }
            
            if (settings.success) settings.success(response);
        }
    });
} 


// Centralized validation error handler
function handleValidationErrors(errors, settings) {
    // Clear previous errors if specified
    if (settings.clearErrors) {
        clearAllErrors(settings);
    }
    
    let errorCount = 0;
    let errorHtml = '<ul class="mb-0">';
    let firstField = null;
    
    // Process each field error
    $.each(errors, function(field, messages) {
        const errorMessage = messages[0];
        
        // Display error under the field
        const errorSpan = $(`${settings.errorSelector}[data-field="${field}"], span.${field}_error`);
        if (errorSpan.length) {
            errorSpan.html(errorMessage);
            errorSpan.show(); // Make sure it's visible
        } else {
            // Fallback: try to find by name attribute
            const input = $(`input[name="${field}"], select[name="${field}"], textarea[name="${field}"]`);
            input.siblings(settings.errorSelector).html(errorMessage);
        }
        
        // Add error class to input field and store first field
        const inputField = $(`input[name="${field}"], select[name="${field}"], textarea[name="${field}"]`);
        inputField.addClass(settings.inputErrorClass);
        
        // Add aria-invalid for accessibility
        inputField.attr('aria-invalid', 'true');
        
        // Store the first field with error
        if (!firstField && inputField.length) {
            firstField = inputField;
        }
        
        // Build global error summary
        errorHtml += `<li><strong>${formatFieldName(field)}:</strong> ${errorMessage}</li>`;
        errorCount++;
    });
    
    errorHtml += '</ul>';
    
    // Show global error alert
    if (settings.showGlobalError && errorCount > 0) {
        showGlobalError(errorHtml, errorCount);
    }
    
    // Scroll to first error with a small delay to ensure DOM is updated
    if (settings.scrollToError && firstField) {
        setTimeout(function() {
            scrollToSpecificField(firstField);
        }, 100);
    }
    
    // Show toast notification
    Notiflix.Notify.warning(`Please fix ${errorCount} error(s) in the form`);
}

function scrollToSpecificField(field) {
    if (!field || !field.length) return;
    
    // Get the field's position
    const fieldOffset = field.offset().top;
    const scrollPosition = fieldOffset - 120; // 120px offset for fixed headers
    
    // Smooth scroll
    $('html, body').animate({
        scrollTop: scrollPosition
    }, 500, function() {
        // Highlight the field
        field.css({
            'transition': 'all 0.3s ease',
            'border-color': '#dc3545',
            'background-color': '#fff8f8'
        });
        
        // Focus on the field
        field.focus();
        
        // Remove highlight after 2 seconds
        setTimeout(function() {
            field.css({
                'border-color': '',
                'background-color': ''
            });
        }, 2000);
    });
}
// Handle general AJAX errors
function handleGeneralError(xhr, settings) {
    let errorMessage = '';
    
    switch (xhr.status) {
        case 429:
            errorMessage = 'Too many requests. Please wait before trying again.';
            Notiflix.Notify.failure('Rate limit exceeded. Please try again later.');
            break;
        case 403:
            errorMessage = 'You do not have permission to perform this action.';
            Notiflix.Notify.failure('Access denied.');
            break;
        case 401:
            errorMessage = 'Please login to continue.';
            Notiflix.Notify.warning('Session expired. Please login again.');
            // setTimeout(() => window.location.href = '/login', 2000);
            break;
        case 404:
            errorMessage = 'The requested resource was not found.';
            Notiflix.Notify.failure('Resource not found.');
            break;
        case 500:
            errorMessage = 'Server error occurred. Please try again later.';
            Notiflix.Notify.failure('Server error. Our team has been notified.');
            break;
        default:
            errorMessage = xhr.responseJSON?.message || 'An unexpected error occurred.';
            Notiflix.Notify.failure(errorMessage);
    }
    
    if (settings.showGlobalError) {
        showGlobalError(`<li>${errorMessage}</li>`, 1);
    }
}

// Clear all errors in a form
function clearAllErrors(settings) {
    // Clear error messages
    $(settings.errorSelector).html('');
    
    // Remove error classes
    $(`.${settings.inputErrorClass}`).removeClass(settings.inputErrorClass);
    
    // Hide global error alert
    $('#global_error_alert').hide();
    $('#global_error_list').html('');
}

// Format field name for display
function formatFieldName(field) {
    const fieldNames = {
        'first_name': 'Full Name',
        'last_name': 'Last Name',
        'email': 'Email Address',
        'password': 'Password',
        'password_confirmation': 'Password Confirmation',
        'address': 'Address',
        'mobile_no': 'Mobile Number',
        'name': 'Name',
        'title': 'Title',
        'description': 'Description'
    };
    return fieldNames[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

    // Scroll to first field with error
    function scrollToFirstError(errorClass = 'is-invalid') {
        // Try multiple selectors to find the first error
        let firstErrorField = $(`.${errorClass}`).first();
        
        // If not found by class, try finding error text spans that have content
        if (!firstErrorField.length) {
            firstErrorField = $('.error-text').filter(function() {
                return $(this).text().trim().length > 0;
            }).first().closest('input, select, textarea');
        }
        
        // If still not found, find any input with error styling
        if (!firstErrorField.length) {
            firstErrorField = $('input.is-invalid, select.is-invalid, textarea.is-invalid').first();
        }
        
        if (firstErrorField && firstErrorField.length) {
            // Get the element's position
            const elementPosition = firstErrorField.offset().top;
            const offsetPosition = elementPosition - 100; // 100px offset from top
            
            // Smooth scroll to the element
            $('html, body').animate({
                scrollTop: offsetPosition
            }, 500, function() {
                // Add highlight effect after scrolling
                firstErrorField.addClass('error-highlight');
                setTimeout(() => {
                    firstErrorField.removeClass('error-highlight');
                }, 2000);
                
                // Focus on the field
                firstErrorField.focus();
            });
        } else {
            console.log('No error fields found to scroll to');
        }
    }
    function scrollToFirstErrorRobust() {
        // List of possible error indicators
        const errorSelectors = [
            '.is-invalid',                          // Bootstrap error class
            'input.error, select.error',            // Generic error class
            '.has-error input, .has-error select',  // Form group error
            '.error-field',                         // Custom error class
            '[aria-invalid="true"]',                // ARIA invalid attribute
            '.text-danger:visible'                  // Visible error messages
        ];
        
        let targetElement = null;
        
        // Try each selector until we find an element
        for (let selector of errorSelectors) {
            targetElement = $(selector).first();
            if (targetElement.length) {
                // If we found a label or span, find its associated input
                if (targetElement.is('span, label, div') && !targetElement.is('input, select, textarea')) {
                    const associatedInput = targetElement.closest('.form-group, .form-field').find('input, select, textarea');
                    if (associatedInput.length) {
                        targetElement = associatedInput;
                    }
                }
                break;
            }
        }
        
        // Also check for error spans with content
        if (!targetElement || !targetElement.length) {
            $('.error-text').each(function() {
                if ($(this).text().trim().length > 0) {
                    const parentInput = $(this).closest('.form-group, .form-field').find('input, select, textarea');
                    if (parentInput.length) {
                        targetElement = parentInput;
                        return false; // Break the loop
                    }
                }
            });
        }
        
        if (targetElement && targetElement.length) {
            const offset = targetElement.offset().top - 120;
            $('html, body').animate({
                scrollTop: offset
            }, 600);
            
            // Add visual feedback
            targetElement.css({
                'transition': 'all 0.3s ease',
                'box-shadow': '0 0 0 3px rgba(220, 53, 69, 0.25)'
            });
            
            setTimeout(() => {
                targetElement.css('box-shadow', '');
            }, 2000);
            
            targetElement.focus();
            return true;
        }
        
        return false;
    }

// Show global error alert
function showGlobalError(errorHtml, errorCount) {
    let alertDiv = $('#global_error_alert');
    
    if (!alertDiv.length) {
        // Create global alert if it doesn't exist
        alertDiv = $(`
            <div id="global_error_alert" class="alert alert-danger" style="display: none;">
                <strong>${errorCount > 1 ? 'Please fix the following errors:' : 'Error:'}</strong>
                <div id="global_error_list"></div>
            </div>
        `);
        $('form').first().prepend(alertDiv);
    }
    
    $('#global_error_list').html(errorHtml);
    alertDiv.show();
    
    // Auto-hide after 8 seconds
    setTimeout(() => alertDiv.fadeOut('slow'), 8000);
}

function ajax_GET(route, data = {}, element, success) {
    $.ajax({
        url: route,
        data: data,
        type: 'GET',
        beforeSend: function () {
            showPulseLoadingNotiflix(element);
        },
        complete: function () {
            hideLoadingNotiflix(element);
        },
        error: function (xhr, status, error) {
            let errors = '';
            $.each(xhr.responseJSON.errors, function (i, v) {
                errors+='- '+v[0]+'<br/>';
            });

            Notiflix.Notify.warning('Code #'+xhr.status+'\n\n'+errors, errors);

            
              
        },
        dataType: 'json',
        success: success
    });
}


function refreshDataTableById(dataTable) {
    $(dataTable).DataTable().ajax.reload(null, false);
}

function refreshDataTable() {
    $('#dataTable').DataTable().ajax.reload();
}


// Real-time validation for all forms
function setupRealtimeValidation() {
    $('form').on('input', 'input, select, textarea', function() {
        const fieldName = $(this).attr('name');
        const errorSpan = $(this).siblings('.error-text').first();
        
        if (errorSpan.length) {
            errorSpan.html('');
        }
        $(this).removeClass('is-invalid');
        
        // Hide global alert
        $('#global_error_alert').fadeOut('slow');
    });
}



// Helper function to show field-specific error
function showFieldError(field, message) {
    const errorSpan = $(`span.${field}_error`);
    if (errorSpan.length) {
        errorSpan.html(message);
        $(`input[name="${field}"]`).addClass('is-invalid');
    }
}