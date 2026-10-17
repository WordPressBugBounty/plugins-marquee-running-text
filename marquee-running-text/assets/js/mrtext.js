/**
 * Marquee Running Text — scrolling engine.
 *
 * Seamless loop: the item list is cloned until it fills the viewport, then the
 * track moves with requestAnimationFrame and wraps after one list width.
 */
( function () {
	'use strict';

	var STORAGE_PREFIX = 'mrtext-closed-';
	var reducedMotion = window.matchMedia ? window.matchMedia( '(prefers-reduced-motion: reduce)' ) : null;

	function storageGet( key ) {
		try {
			return window.localStorage.getItem( key );
		} catch ( e ) {
			return null;
		}
	}

	function storageSet( key, value ) {
		try {
			window.localStorage.setItem( key, value );
		} catch ( e ) {}
	}

	function Marquee( el ) {
		this.el = el;
		this.viewport = el.querySelector( '.mrtext__viewport' );
		this.track = el.querySelector( '.mrtext__track' );
		this.group = el.querySelector( '.mrtext__group' );
		this.pauseBtn = el.querySelector( '.mrtext__pause' );
		this.closeBtn = el.querySelector( '.mrtext__close' );
		this.preview = el.classList.contains( 'mrtext--preview' );

		this.speed = Math.max( 1, parseFloat( el.getAttribute( 'data-speed' ) ) || 60 );
		this.direction = 'right' === el.getAttribute( 'data-direction' ) ? 1 : -1;
		this.offset = 0;
		this.width = 0;
		this.last = 0;
		this.frame = 0;
		this.hovered = false;
		this.focused = false;
		this.userPaused = false;
		this.visible = true;
		this.effectName = el.getAttribute( 'data-effect' ) || 'scroll';
		this.effect = null;

		this.tick = this.tick.bind( this );
	}

	Marquee.prototype.init = function () {
		var self = this;
		var el = this.el;

		if ( ! this.viewport || ! this.track || ! this.group ) {
			return;
		}

		if ( '1' === el.getAttribute( 'data-closable' ) && ! this.preview && storageGet( STORAGE_PREFIX + el.getAttribute( 'data-key' ) ) ) {
			el.hidden = true;
			return;
		}

		if ( el.classList.contains( 'mrtext--relocate' ) && ! this.preview && document.body ) {
			document.body.insertBefore( el, document.body.firstChild );
		}

		if ( el.classList.contains( 'mrtext--bottom' ) && ! this.preview ) {
			this.updateBodyPadding();
		}

		if ( reducedMotion && reducedMotion.matches ) {
			el.classList.add( 'mrtext--reduced' );
			this.userPaused = true;
			this.syncPauseButton();
		}

		if ( '1' === el.getAttribute( 'data-pause-on-hover' ) ) {
			el.addEventListener( 'mouseenter', function () {
				self.hovered = true;
				self.update();
			} );
			el.addEventListener( 'mouseleave', function () {
				self.hovered = false;
				self.update();
			} );
		}

		// Keyboard users can read a link without it moving away.
		el.addEventListener( 'focusin', function () {
			self.focused = true;
			self.update();
		} );
		el.addEventListener( 'focusout', function () {
			self.focused = false;
			self.update();
		} );

		if ( this.pauseBtn ) {
			this.pauseBtn.addEventListener( 'click', function () {
				self.userPaused = ! self.userPaused;
				if ( ! self.userPaused ) {
					el.classList.remove( 'mrtext--reduced' );
					self.viewport.scrollLeft = 0;
				}
				self.syncPauseButton();
				self.update();
			} );
		}

		if ( this.closeBtn ) {
			this.closeBtn.addEventListener( 'click', function () {
				el.hidden = true;
				self.stop();
				if ( ! self.preview ) {
					storageSet( STORAGE_PREFIX + el.getAttribute( 'data-key' ), '1' );
					if ( el.classList.contains( 'mrtext--bottom' ) ) {
						document.body.style.paddingBottom = '';
					}
				}
			} );
		}

		if ( window.IntersectionObserver ) {
			new window.IntersectionObserver( function ( entries ) {
				self.visible = entries[ entries.length - 1 ].isIntersecting;
				self.update();
			} ).observe( el );
		}

		document.addEventListener( 'visibilitychange', function () {
			self.update();
		} );

		// Other effects (vertical, fade, typewriter) are registered by add-ons in window.mrtextEffects.
		if ( 'scroll' !== this.effectName && window.mrtextEffects && window.mrtextEffects[ this.effectName ] ) {
			this.effect = window.mrtextEffects[ this.effectName ]( this );
			this.update();
			return;
		}

		if ( window.ResizeObserver ) {
			new window.ResizeObserver( function () {
				self.fill();
			} ).observe( this.group );
			new window.ResizeObserver( function () {
				self.fill();
			} ).observe( this.viewport );
		} else {
			window.addEventListener( 'resize', function () {
				self.fill();
			} );
		}

		this.fill();
		this.update();
	};

	Marquee.prototype.syncPauseButton = function () {
		if ( ! this.pauseBtn ) {
			return;
		}
		this.pauseBtn.setAttribute( 'aria-pressed', this.userPaused ? 'true' : 'false' );
		this.pauseBtn.setAttribute(
			'aria-label',
			this.pauseBtn.getAttribute( this.userPaused ? 'data-label-play' : 'data-label-pause' ) || ''
		);
	};

	Marquee.prototype.updateBodyPadding = function () {
		document.body.style.paddingBottom = this.el.offsetHeight + 'px';
	};

	// Clone the list until two viewport widths are covered, so the loop never shows a gap.
	Marquee.prototype.fill = function () {
		var clones = this.track.querySelectorAll( '.mrtext__group--clone' );
		var width = this.group.getBoundingClientRect().width;
		var needed;
		var i;
		var clone;

		if ( ! width ) {
			return;
		}

		needed = Math.ceil( this.viewport.clientWidth / width ) + 1;

		if ( clones.length !== needed ) {
			for ( i = 0; i < clones.length; i++ ) {
				clones[ i ].parentNode.removeChild( clones[ i ] );
			}
			for ( i = 0; i < needed; i++ ) {
				clone = this.group.cloneNode( true );
				clone.classList.add( 'mrtext__group--clone' );
				clone.setAttribute( 'aria-hidden', 'true' );
				Array.prototype.forEach.call( clone.querySelectorAll( 'a, button' ), function ( node ) {
					node.setAttribute( 'tabindex', '-1' );
				} );
				this.track.appendChild( clone );
			}
		}

		if ( width !== this.width ) {
			this.width = width;
			this.offset = this.direction > 0 ? -width : 0;
			this.render();
		}
	};

	Marquee.prototype.isRunning = function () {
		return ( this.effect || this.width > 0 ) && this.visible && ! this.hovered && ! this.focused && ! this.userPaused && ! document.hidden;
	};

	Marquee.prototype.update = function () {
		if ( this.effect ) {
			this.effect.toggle( this.isRunning() );
			return;
		}
		if ( this.isRunning() ) {
			if ( ! this.frame ) {
				this.last = 0;
				this.frame = window.requestAnimationFrame( this.tick );
			}
		} else {
			this.stop();
		}
	};

	Marquee.prototype.stop = function () {
		if ( this.effect ) {
			this.effect.toggle( false );
		}
		if ( this.frame ) {
			window.cancelAnimationFrame( this.frame );
			this.frame = 0;
		}
	};

	Marquee.prototype.tick = function ( now ) {
		var delta;

		this.frame = 0;
		if ( ! this.el.isConnected || ! this.isRunning() ) {
			return;
		}

		if ( this.last ) {
			delta = Math.min( 0.1, ( now - this.last ) / 1000 );
			this.offset += this.direction * this.speed * delta;

			if ( this.offset <= -this.width ) {
				this.offset += this.width;
			} else if ( this.offset > 0 ) {
				this.offset -= this.width;
			}
			this.render();
		}

		this.last = now;
		this.frame = window.requestAnimationFrame( this.tick );
	};

	Marquee.prototype.render = function () {
		this.track.style.transform = 'translate3d(' + this.offset.toFixed( 2 ) + 'px,0,0)';
	};

	/**
	 * Starts every marquee inside root (or root itself). Safe to call more than once.
	 *
	 * @param {Element|Document} [root] Container.
	 */
	function init( root ) {
		var nodes;

		root = root || document;
		nodes = root.classList && root.classList.contains( 'mrtext' ) ? [ root ] : root.querySelectorAll( '.mrtext' );

		Array.prototype.forEach.call( nodes, function ( el ) {
			if ( el.mrtextInstance ) {
				return;
			}
			el.mrtextInstance = new Marquee( el );
			el.mrtextInstance.init();
		} );
	}

	window.mrtextInit = init;

	if ( 'complete' === document.readyState ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', function () {
			init();
		} );
		window.addEventListener( 'load', function () {
			init();
		} );
	}

	// Elementor editor re-renders widgets without reloading the page.
	window.addEventListener( 'elementor/frontend/init', function () {
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/mrtext-marquee.default', function ( $scope ) {
				init( $scope[ 0 ] );
			} );
		}
	} );
}() );
