class VamtamForm extends elementorModules.frontend.handlers.Base {

	onInit( ...args ) {
		super.onInit( ...args );

		this.checkHandleBtnUnderlineAnimation();
	}

	checkHandleBtnUnderlineAnimation() {
		const isEditor = jQuery( 'body' ).hasClass( 'elementor-editor-active' );

		if ( isEditor ) {
			// Editor.
			setTimeout( () => {
				this.handleBtnUnderlineAnimation();
			}, 2000 );
		} else {
			// Frontend.
			if ( document.readyState == 'complete' ) {
				// Forms that are added later in the page (popups).
				setTimeout(() => {
					this.handleBtnUnderlineAnimation();
				}, 25 );
			} else {
				// Page load (forms that are already on page).
				// load event cause form buttons are injected by js.
				jQuery( window ).on( 'load', () => {
					this.handleBtnUnderlineAnimation();
				} );
			}
		}
	}

	handleBtnUnderlineAnimation() {
		if ( ! this.$element.hasClass( 'vamtam-has-underline-anim' ) ) {
			return;
		}

		/*
			Because on form buttons the text container is using flex, all its children are forced to block-level.
			We need inline for the underline animation to work properly on multiline text so we add a new
			nested span.

			TODO: Maybe do this on server.
		*/
		const formBtns = this.$element.find( '.elementor-button:not(:empty)' ); // form buttons with text.
		jQuery.each( formBtns, ( i, formBtn ) => {
			const $formBtn     = jQuery( formBtn ),
				isSubmit       = $formBtn.filter( '[type="submit"]' ).length,
				$formBtnTextEl = isSubmit ? $formBtn.find( '.elementor-button-text' ).first() : $formBtn, // for submit, the text is on a different element.
				btnText        = $formBtnTextEl.text();

			$formBtnTextEl.text( '' );
			if ( isSubmit ) {
				// submit already has a wrapper element.
				$formBtnTextEl.append( '<span class="vamtam-btn-text">' + btnText + '</span>' );
			} else {
				// next/prev form buttons.
				$formBtnTextEl.append( '<span class="vamtam-btn-text-wrap"><span class="vamtam-btn-text">' + btnText + '</span></span>' );
			}
		} );

		// Add class on hover to trigger the animation.
		jQuery( this.$element ).on( 'mouseenter', '.elementor-button', ( e ) => {
			const $el = jQuery( e.target );
			if ( $el.hasClass( 'hovered' ) ) {
				return;
			}

			$el.addClass( 'hovered' );

			// This timeout is used as a guard to avoid flickering caused by very fast chnages of hover state.
			setTimeout(() => {
				$el.removeClass( 'hovered' );
			}, 600 );
		} );
	}
}


jQuery( window ).on( 'elementor/frontend/init', () => {
	if ( ! elementorFrontend.elementsHandler || ! elementorFrontend.elementsHandler.attachHandler ) {
		const addHandler = ( $element ) => {
			elementorFrontend.elementsHandler.addHandler( VamtamForm, {
				$element,
			} );
		};

		elementorFrontend.hooks.addAction( 'frontend/element_ready/form.default', addHandler, 100 );
	} else {
		elementorFrontend.elementsHandler.attachHandler( 'form', VamtamForm );
	}
} );
