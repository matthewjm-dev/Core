// Add Loader
function addLoader($element, message = '') {
    if (message) {
        message = '<div class="loader-message">' + message + '</div>';
    }
    $element.prepend('<div class="loader active"><div class="loader-icon"></div>' + message + '</div>');
}

// Remove Loader
function removeLoader($element) {
    $element.find('.loader').remove();
}