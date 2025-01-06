$( function() {
    $( '.existing-settings input.form-control' ).on( 'change', function() {
        $( 'input[type="checkbox"]', $( this ).parent().parent() ).attr( 'checked', true );
    } );
} );