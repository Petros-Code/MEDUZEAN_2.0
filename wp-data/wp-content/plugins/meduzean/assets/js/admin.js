// admin.js - small helpers for EAN Manager admin pages
( function ( window, document, $ ) {
    'use strict';

    $( document ).ready( function () {
        console.log( 'Meduzean EAN Manager admin loaded' );

        // Example: AJAX fetch of eans sample (can be used for advanced UI)
        // function fetchEans() {
        //     fetch( meduzean.rest_url + '/ean', {
        //         headers: { 'X-WP-Nonce': meduzean.nonce }
        //     } ).then( r => r.json() ).then( data => console.log( data ) );
        // }
    } );
} )( window, document, jQuery );
