/*!
	Popup plugin v1.0
	Info: This plugin is designed for simple, functional popup dialogs.
	Author: Matthew Murray ( admin@matdragon.com )

	--| Update Log |--

	- v1.0

*/

function show_popup( $content, popup_class ) {
	if ( !$( 'body' ).find( '#popup' ).length ) {
		var popup_close = '<div id="popup-close">x</div>';
		var popup = '<div id="popup" class="' + popup_class + '">' + popup_close + $content + '</div>';
		var cover = '<div id="popup-cover" class="' + popup_class + '"></div>';

		$( 'body' ).prepend( cover );
		$( 'body' ).prepend( popup );

		return $( 'body' ).find( '#popup.' + popup_class.replace(' ', '.') );
	}
}

function remove_popup( popup ) {
	var popup_class = popup.attr('class').replace(' ', '.');
	var toclose = '#popup.' + popup_class + ', #popup-cover.' + popup_class;

	$( 'body' ).find( toclose ).fadeOut( function() {
		$( 'body' ).find( toclose ).remove();
	} );
}

$( document ).ready( function() {
	var closes = '#popup-close, .popup-close';

	$( 'body' ).on( 'click', closes, function() {
		var popup = $( this ).parents( '#popup' );
		remove_popup( popup );
	});

	var covercloses = '#popup-cover';
	$( 'body' ).on( 'click', covercloses, function() {
		var popup_cover_class = $(this).attr('class').replace(' ', '.');
		var popup = $( '#popup.' + popup_cover_class );
		remove_popup( popup );
	});

} );
