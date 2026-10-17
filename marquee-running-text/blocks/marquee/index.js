/**
 * Marquee Running Text block — editor script (no build step required).
 */
( function ( blocks, blockEditor, components, element, i18n, ServerSideRender ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var data = window.mrtextBlockData || { marquees: [], manageUrl: '' };

	blocks.registerBlockType( 'marquee-running-text/marquee', {
		edit: function ( props ) {
			var blockProps = blockEditor.useBlockProps();
			var options = [ { value: 0, label: __( 'Default marquee', 'marquee-running-text' ) } ].concat(
				data.marquees.map( function ( marquee ) {
					return {
						value: marquee.id,
						label: marquee.title + ( marquee.active ? '' : ' ' + __( '(inactive)', 'marquee-running-text' ) ),
					};
				} )
			);

			return el(
				'div',
				blockProps,
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'Marquee', 'marquee-running-text' ) },
						el( components.SelectControl, {
							label: __( 'Choose a marquee', 'marquee-running-text' ),
							value: props.attributes.marqueeId,
							options: options,
							onChange: function ( value ) {
								props.setAttributes( { marqueeId: parseInt( value, 10 ) || 0 } );
							},
						} ),
						data.manageUrl
							? el(
								'p',
								null,
								el( 'a', { href: data.manageUrl, target: '_blank', rel: 'noopener noreferrer' }, __( 'Manage marquees', 'marquee-running-text' ) )
							)
							: null
					)
				),
				el( ServerSideRender, {
					block: 'marquee-running-text/marquee',
					attributes: props.attributes,
				} )
			);
		},
		save: function () {
			return null;
		},
	} );
}(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.element,
	window.wp.i18n,
	window.wp.serverSideRender
) );
