/**
 * Marquee Running Text — admin screens.
 */
jQuery( function ( $ ) {
	'use strict';

	var i18n = ( window.mrtextAdmin && window.mrtextAdmin.i18n ) || {};

	// Copy shortcode buttons.
	$( document ).on( 'click', '.mrtext-copy__btn', function () {
		var $btn = $( this );
		var input = $btn.siblings( 'input' ).get( 0 );
		var label = $btn.text();

		input.select();
		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( input.value );
		} else {
			document.execCommand( 'copy' );
		}
		$btn.text( i18n.copied || label );
		setTimeout( function () {
			$btn.text( label );
		}, 1500 );
	} );

	$( document ).on( 'click', '.mrtext-delete', function ( event ) {
		if ( ! window.confirm( i18n.confirmDelete ) ) {
			event.preventDefault();
		}
	} );

	var $form = $( '#mrtext-editor' );
	if ( ! $form.length ) {
		return;
	}

	var $items = $( '#mrtext-items' );
	var $preview = $( '#mrtext-preview' );
	var nextIndex = $items.children().length;
	var timer = 0;
	var dirty = false;
	var filters = {};

	// Small extension API for add-ons (used by the Pro version).
	window.mrtextEditor = {
		addFilter: function ( name, callback ) {
			( filters[ name ] = filters[ name ] || [] ).push( callback );
		},
		nextIndex: function () {
			return nextIndex++;
		},
		refresh: function () {
			changed();
		},
		escapeHtml: escapeHtml,
		formatText: formatText,
	};

	function applyFilters( name, value, extra ) {
		( filters[ name ] || [] ).forEach( function ( callback ) {
			value = callback( value, extra, $form );
		} );
		return value;
	}

	/* Tabs */
	$form.on( 'click', '.mrtext-tabs__tab', function () {
		var $tab = $( this );
		$tab.addClass( 'is-active' ).attr( 'aria-selected', 'true' )
			.siblings().removeClass( 'is-active' ).attr( 'aria-selected', 'false' );
		$form.find( '.mrtext-panel' ).prop( 'hidden', true );
		$( '#' + $tab.attr( 'aria-controls' ) ).prop( 'hidden', false );
	} );

	/* Item repeater */
	$items.sortable( {
		handle: '.mrtext-item__handle',
		axis: 'y',
		placeholder: 'mrtext-item mrtext-item--placeholder',
		update: changed,
	} );

	$( '#mrtext-add-item' ).on( 'click', function () {
		var html = $( '#tmpl-mrtext-item' ).html().replace( /__INDEX__/g, String( nextIndex++ ) );
		$items.append( html );
		$items.children().last().find( '.mrtext-item__text' ).trigger( 'focus' );
		changed();
	} );

	$items.on( 'click', '.mrtext-item__remove', function () {
		$( this ).closest( '.mrtext-item' ).remove();
		changed();
	} );

	/* Fields */
	$form.find( '.mrtext-color' ).wpColorPicker( {
		change: changed,
		clear: changed,
	} );

	$form.on( 'input change', ':input', changed );

	$form.on( 'input', '.mrtext-range', function () {
		$( this ).siblings( '.mrtext-range-value' ).text( this.value );
	} );

	$form.on( 'change', '[name="mrtext[style][separator]"]', toggleSeparatorChar );
	toggleSeparatorChar();

	$form.on( 'submit', function () {
		dirty = false;
	} );

	$( window ).on( 'beforeunload', function () {
		if ( dirty ) {
			return i18n.unsaved;
		}
	} );

	function toggleSeparatorChar() {
		$form.find( '.mrtext-row-separator-char' ).toggle( 'custom' === field( 'style', 'separator' ) );
	}

	function changed() {
		dirty = true;
		clearTimeout( timer );
		timer = setTimeout( updatePreview, 120 );
	}

	/* Reading the form */
	function field( group, key ) {
		var $el = $form.find( '[name="mrtext[' + group + '][' + key + ']"]' ).not( '[type=hidden]' );
		if ( $el.is( ':radio' ) ) {
			return $el.filter( ':checked' ).val();
		}
		if ( $el.is( ':checkbox' ) ) {
			return $el.is( ':checked' );
		}
		return $el.val();
	}

	function color( key ) {
		var $el = $form.find( '[name="mrtext[style][' + key + ']"]' );
		var value = $el.val();
		return /^#[0-9a-f]{3}([0-9a-f]{3})?$/i.test( value ) ? value : $el.data( 'default-color' );
	}

	function readConfig() {
		var items = [];

		$items.children( '.mrtext-item' ).each( function () {
			var $row = $( this );
			var text = $.trim( $row.find( '.mrtext-item__text' ).val() || '' );
			var item = text ? { type: 'text', text: text, link: $.trim( $row.find( '.mrtext-item__link' ).val() || '' ) } : null;

			item = applyFilters( 'readItem', item, $row );
			if ( item ) {
				items.push( item );
			}
		} );

		return applyFilters( 'config', {
			items: items,
			label: field( 'label', 'enabled' ) ? $.trim( field( 'label', 'text' ) ) : '',
			style: {
				bg: color( 'bg_color' ),
				text: color( 'text_color' ),
				hover: color( 'hover_color' ),
				sep: color( 'separator_color' ),
				labelBg: color( 'label_bg' ),
				labelColor: color( 'label_color' ),
				fontSize: ( parseFloat( field( 'style', 'font_size' ) ) || 16 ) + field( 'style', 'font_unit' ),
				weight: field( 'style', 'font_weight' ),
				height: ( parseInt( field( 'style', 'height' ), 10 ) || 40 ) + 'px',
				radius: ( parseInt( field( 'style', 'border_radius' ), 10 ) || 0 ) + 'px',
				gap: ( parseInt( field( 'style', 'gap' ), 10 ) || 0 ) + 'px',
				separator: field( 'style', 'separator' ),
				separatorChar: field( 'style', 'separator_char' ) || '',
			},
			motion: {
				direction: field( 'motion', 'direction' ),
				speed: field( 'motion', 'speed' ),
				pauseOnHover: field( 'motion', 'pause_on_hover' ),
				pauseButton: field( 'motion', 'pause_button' ),
			},
			closable: field( 'display', 'closable' ),
		} );
	}

	/* Building the preview (mirrors MRText_Renderer) */
	function escapeHtml( value ) {
		return String( value ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	// Only the tags the server allows are restored after escaping.
	function formatText( value ) {
		return escapeHtml( value ).replace( /&lt;(\/?)(strong|b|em|i|u)&gt;/gi, '<$1$2>' );
	}

	function separatorHtml( style ) {
		switch ( style.separator ) {
			case 'none':
				return '';
			case 'star':
				return '<span class="mrtext__sep mrtext__sep--char" aria-hidden="true">★</span>';
			case 'custom':
				return style.separatorChar ? '<span class="mrtext__sep mrtext__sep--char" aria-hidden="true">' + escapeHtml( style.separatorChar ) + '</span>' : '';
			default:
				return '<span class="mrtext__sep mrtext__sep--' + style.separator + '" aria-hidden="true"></span>';
		}
	}

	function buildPreview( config ) {
		var s = config.style;
		var sep = applyFilters( 'separatorHtml', separatorHtml( s ), config );
		var html = '';
		var vars = [
			'--mrtext-bg:' + s.bg,
			'--mrtext-color:' + s.text,
			'--mrtext-hover:' + s.hover,
			'--mrtext-sep:' + s.sep,
			'--mrtext-label-bg:' + s.labelBg,
			'--mrtext-label-color:' + s.labelColor,
			'--mrtext-font-size:' + s.fontSize,
			'--mrtext-weight:' + s.weight,
			'--mrtext-height:' + s.height,
			'--mrtext-radius:' + s.radius,
			'--mrtext-gap:' + s.gap,
		].join( ';' );

		config.items.forEach( function ( item ) {
			var inner = '';
			if ( 'text' === item.type ) {
				inner = item.link ?
					'<a href="#" onclick="return false" class="mrtext__link">' + formatText( item.text ) + '</a>' :
					'<span class="mrtext__link">' + formatText( item.text ) + '</span>';
			}
			inner = applyFilters( 'itemHtml', inner, item );
			if ( inner ) {
				html += '<li class="mrtext__item">' + sep + inner + '</li>';
			}
		} );

		return $(
			'<div class="mrtext mrtext--inline mrtext--preview" role="marquee"' +
				' style="' + escapeHtml( vars ) + '"' +
				' data-speed="' + escapeHtml( config.motion.speed ) + '"' +
				' data-direction="' + escapeHtml( config.motion.direction ) + '"' +
				' data-pause-on-hover="' + ( config.motion.pauseOnHover ? '1' : '0' ) + '"' +
				' data-closable="0">' +
				( config.label ? '<span class="mrtext__label">' + escapeHtml( config.label ) + '</span>' : '' ) +
				'<div class="mrtext__viewport"><div class="mrtext__track"><ul class="mrtext__group">' + html + '</ul></div></div>' +
				( config.motion.pauseButton ? '<button type="button" class="mrtext__btn mrtext__pause" aria-pressed="false" aria-label="' + escapeHtml( i18n.pause ) + '" data-label-pause="' + escapeHtml( i18n.pause ) + '" data-label-play="' + escapeHtml( i18n.play ) + '"><span class="mrtext__icon" aria-hidden="true"></span></button>' : '' ) +
				( config.closable ? '<button type="button" class="mrtext__btn mrtext__close" aria-label="' + escapeHtml( i18n.close ) + '"><span aria-hidden="true">&times;</span></button>' : '' ) +
			'</div>'
		);
	}

	function updatePreview() {
		var config = readConfig();
		var $marquee;

		$preview.empty();
		if ( ! config.items.length ) {
			$preview.append( $( '<p class="mrtext-preview__empty"></p>' ).text( i18n.emptyPreview ) );
			return;
		}

		$marquee = applyFilters( 'marquee', buildPreview( config ), config );
		$preview.append( $marquee );
		if ( window.mrtextInit ) {
			window.mrtextInit( $marquee.get( 0 ) );
		}
		applyFilters( 'rendered', $marquee, config );
	}

	updatePreview();
} );
