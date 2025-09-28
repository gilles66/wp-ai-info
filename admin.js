/*
 * Admin script for WP AI Info: generate image metadata via AJAX.
 */
jQuery( function ( $ ) {
    $( '#wp-ai-info-generate-image' ).on( 'click', function ( e ) {
        e.preventDefault();
        var button = $( this );
        var attachmentId = button.data( 'attachment-id' );
        var nonce = $( '#wp_ai_info_image_nonce' ).val();
        $( '#wp-ai-info-image-result' ).text( 'Génération en cours...' );
        button.prop( 'disabled', true );
        $.post( ajaxurl, {
            action: 'wp_ai_info_generate_image_fields',
            attachment_id: attachmentId,
            _wpnonce: nonce
        }, function ( response ) {
            if ( response.success ) {
                $( '#wp-ai-info-image-result' ).html( '<p style="color:green;">Champs générés avec succès.</p>' );
                setTimeout( function () {
                    location.reload();
                }, 1500 );
            }
            else {
                $( '#wp-ai-info-image-result' ).html( '<p style="color:red;">Erreur: ' + response.data + '</p>' );
                button.prop( 'disabled', false );
            }
        } );
    } );
} );