class VamtamHeading extends elementorModules.frontend.handlers.Base {
	getDefaultSettings() {
		return {
			selectors: {
				heading: '.elementor-heading-title',
			},
		};
	}

	getDefaultElements() {
		const selectors = this.getSettings( 'selectors' );
		return {
			$heading: this.$element.find( selectors.heading ),
		};
	}

	onInit( ...args ) {
		super.onInit( ...args );
		this.handleTextRevealAnim();
	}

	handleTextRevealAnim() {
		if ( ! this.$element.hasClass( 'vamtam-has-text-reveal-anim' ) ) {
			return;
		}

		const paragraph = this.elements.$heading[ 0 ];
		const wordArr = paragraph.textContent.split(' ');

		let html = '';
		for ( let i = 0; i < wordArr.length; i++ ) {
			html += `<span class="vamtam-tra-word">${ wordArr[ i ] } </span>`;
		}

		paragraph.innerHTML = html;

		const words = [ ...paragraph.querySelectorAll( 'span.vamtam-tra-word' ) ];

		const getParagraphRect = () => {
			var rect = paragraph.getBoundingClientRect();

			var inViewport = (
				rect.top < window.innerHeight &&
				rect.bottom > 0 &&
				rect.left < window.innerWidth &&
				rect.right > 0
			);

			return {
				inViewport,
				top: rect.top,
				bottom: rect.bottom,
				left: rect.left,
				right: rect.right,
			};
		}

		let isfirstRun = true;
		let pRect = {};
		let isMaxDeviceWidth = window.VAMTAM.isMaxDeviceWidth();

		const textReveal = () => {
			for ( let i = 0; i < words.length; i++ ) {
				if ( ! pRect.inViewport && ! isfirstRun ) {
					break;
				}

				if ( pRect.inViewport ) {
					let { left, top } = words[ i ].getBoundingClientRect();
					top = top - ( window.innerHeight * 0.5 );

					let opacityValue = 1 - ( ( top * 0.01 ) + ( left * 0.001 ) );
					opacityValue = opacityValue < 0.1 ? 0.1 : opacityValue > 1 ? 1 : opacityValue.toFixed( 3 );

					if ( isfirstRun ) {
						isfirstRun = false;
						jQuery( paragraph ).css( 'opacity', 1 );
					}

					words[ i ].style.opacity = opacityValue;
				} else if ( pRect.bottom < 0 ) {
					//	Scrolled past the paragraph during page load (first-run).
					isfirstRun = false;
					jQuery( paragraph ).css( 'opacity', 1 );
				}
			}
		}

		window.VAMTAM.addScrollHandler( {
			init: () => {
				if ( isMaxDeviceWidth ) {
					pRect = getParagraphRect();
					textReveal();
				}
			},
			measure: () => {
				if ( isMaxDeviceWidth ) {
					pRect = getParagraphRect();
				}
			},
			mutate: () => {
				if ( isMaxDeviceWidth ) {
					textReveal()
				}
			},
		} );

		window.addEventListener( 'resize', window.VAMTAM.debounce( () => {
			isMaxDeviceWidth = window.VAMTAM.isMaxDeviceWidth();
		} , 100 ), false );
	}
}


jQuery( window ).on( 'elementor/frontend/init', () => {
	if ( ! elementorFrontend.elementsHandler || ! elementorFrontend.elementsHandler.attachHandler ) {
		const addHandler = ( $element ) => {
			elementorFrontend.elementsHandler.addHandler( VamtamHeading, {
				$element,
			} );
		};
		elementorFrontend.hooks.addAction( 'frontend/element_ready/heading.default', addHandler, 100 );
	} else {
		elementorFrontend.elementsHandler.attachHandler( 'heading', VamtamHeading );
	}
} );
