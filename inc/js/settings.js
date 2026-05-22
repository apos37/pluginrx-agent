jQuery( function ( $ ) {

    // Generate API Key
    $( '#prxagnt-generate-api-key' ).on( 'click', function( e ) {
        e.preventDefault();

        var $display = $( '#prxagnt-api-key-display' );

        // Show animated "Generating..." message
        $display.html( '<span class="prxagnt-loader">' + prxagnt_settings.generating + '</span>' );

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'prxagnt_generate_api_key',
                nonce: prxagnt_settings.nonce
            },
            success: function( response ) {
                if ( response.success && response.data.api_key ) {
                    $display.text( response.data.api_key );
                } else {
                    $display.text( 'Failed to generate key' );
                }
            },
            error: function() {
                $display.text( 'Failed to generate key' );
            }
        } );
    } );

    // Copy API Key
    $( '#prxagnt-copy-api-key' ).on( 'click', function( e ) {
        e.preventDefault();

        var apiKey = $( '#prxagnt-api-key-display' ).text().trim();
        var $button = $( this );

        function showCheck() {
            var $check = $button.siblings( '.prxagnt-copy-check' );
            if ( $check.length === 0 ) {
                $check = $( '<span class="prxagnt-copy-check">&#10003;</span>' );
                $button.after( $check );
            }
            $check.addClass( 'visible' );
            setTimeout( function() {
                $check.removeClass( 'visible' );
            }, 2000 );
        }

        if ( navigator.clipboard && navigator.clipboard.writeText ) {
            navigator.clipboard.writeText( apiKey ).then( function() {
                showCheck();
            } ).catch( function( err ) {
                console.error( 'Failed to copy API key.', err );
            } );
        } else {
            var $temp = $( '<textarea>' );
            $( 'body' ).append( $temp );
            $temp.val( apiKey ).select();
            try {
                document.execCommand( 'copy' );
                showCheck();
            } catch ( err ) {
                console.error( 'Failed to copy API key.', err );
            }
            $temp.remove();
        }
    } );

} );
