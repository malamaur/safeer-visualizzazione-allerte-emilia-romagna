/* Allerta Meteo ER – apertura/chiusura delle finestre Info e Legenda */
( function () {
	document.addEventListener( 'click', function ( e ) {
		var opener = e.target.closest( '[data-amer-open]' );
		if ( opener ) {
			var dlg = document.getElementById( opener.getAttribute( 'data-amer-open' ) );
			if ( dlg && typeof dlg.showModal === 'function' ) {
				dlg._amerOpener = opener;
				dlg.showModal();
			}
			return;
		}
		var closer = e.target.closest( '[data-amer-close]' );
		if ( closer ) {
			closer.closest( 'dialog' ).close();
			return;
		}
		// Clic sullo sfondo scuro: chiude.
		if ( e.target.classList && e.target.classList.contains( 'amer__dialog' ) ) {
			e.target.close();
		}
	} );
	document.addEventListener( 'close', function ( e ) {
		if ( e.target._amerOpener ) {
			e.target._amerOpener.focus();
		}
	}, true );
} )();
