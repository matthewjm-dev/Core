/*!
	Form Errors v1.0
	Info: Received a set of form input key / values and displays the error messages
	Author: Matthew Murray ( matthew@exleysmith.com )
*/
//jQuery( function( $ ) {

	var error_methods = {

		'add_error': function( $form, target, message ) {
			var $field = $( '#' + target );

			if ( $field.length ) {
				var $fieldset = $field.parents( 'fieldset' );
				var content = '<p class="message">' + message + '</p>';

				$fieldset.addClass( 'error' ).append( content );
			} else {
				var content = '<p class="error message">' + message + '<i class="fas fa-exclamation-circle"></i></p>';

				error_methods.add_message( $form, content );
			}
		},

		'add_message': function( $form, content ) {
			$form.prepend( content );
		},

		'remove_error': function( $target ) {
			$target.removeClass( 'error' ).find( '.message' ).remove();
		},

		'clear_form': function( $form ) {
			$form.find( '.message' ).remove();
			$form.find( '.error' ).removeClass( 'error' );
		},

		'watch_errors': function( $form ) {
			$form.on( 'click', 'input, focus, select, textarea', function() {
				var $fieldset = $( this ).parents( 'fieldset' );
				if ( $fieldset.hasClass( 'error') ) {
					error_methods.remove_error( $fieldset );
				}
			} );
		}
	}

	function show_errors( $form, errors ) {
		error_methods.clear_form( $form );

		$.each( errors, function( input, message ) {
			error_methods.add_error( $form, input, message );
		} );

		error_methods.watch_errors( $form );
	}

	function show_success( $form, message ) {
		error_methods.clear_form( $form );

		var content = '<p class="success message">' + message + '</p>';

		error_methods.add_message( $form, content );
	}

//});