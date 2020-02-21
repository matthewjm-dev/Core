// Add a Success flash message
function add_flash_success( message ) {
    add_flash(message, 'success');
}

// Add an Error flash message
function add_flash_error( message ) {
    add_flash(message, 'error');
}

// Add a flash message
function add_flash( message, type ) {
    if ($('#flash-messages').length) {
        var $content = $('<p class="message ' + type + '">' + message + '<span>Dismiss<i class="far fa-times-circle"></i></span></p>');

        $('#flash-messages .container').prepend($content);

        // Remove the created flash message after 3 seconds
        setTimeout(function() {
            $content.fadeOut(400, function() {
                $content.remove();
            });
        }, 3000);
    }
}

// Remove flash messages after 3 seconds that exist on page load
$( document ).ready( function() {
    setTimeout(function() {
        console.log('removing existing flash messages');
        var $flash = $('section#flash-messages .container p.message');
        $flash.fadeOut(400, function() {
            $flash.remove();
        });
    }, 3000);
});