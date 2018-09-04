const { __ } = wp.i18n;
const {
	SelectControl,
	RangeControl
} = wp.components;

const ANIMATION_DEFAULTS = {
	drop: 1600,
	'fade-in': 500,
	'fly-in-bottom': 500,
	'fly-in-left': 500,
	'fly-in-right': 500,
	'fly-in-top': 500,
	pulse: 500,
	'rotate-in-left': 700,
	'rotate-in-right': 700,
	'twirl-in': 1000,
	'whoosh-in-left': 500,
	'whoosh-in-right': 500,
	'pan-left': 1000,
	'pan-right': 1000,
	'pan-down': 1000,
	'pan-up': 1000,
	'zoom-in': 1000,
	'zoom-out': 1000
};

/**
 * Animation controls for AMP Story layout blocks'.
 *
 * @param {Function} setAttributes Set Attributes.
 * @param {Object} attributes Props.
 * @return {[XML,*,XML,*,XML]} Controls.
 */
export function getAmpStoryAnimationControls( setAttributes, attributes ) {
	let placeHolder = ANIMATION_DEFAULTS[ attributes.animationType ] || 0;
	return [
		<SelectControl
			key="animation"
			label={ __( 'Animation Type', 'amp' ) }
			value={ attributes.animationType }
			options={ [
				{
					value: '',
					label: __( 'None', 'amp' )
				},
				{
					value: 'drop',
					label: __( 'Drop', 'amp' )
				},
				{
					value: 'fade-in',
					label: __( 'Fade In', 'amp' )
				},
				{
					value: 'fly-in-bottom',
					label: __( 'Fly In Bottom', 'amp' )
				},
				{
					value: 'fly-in-left',
					label: __( 'Fly In Left', 'amp' )
				},
				{
					value: 'fly-in-right',
					label: __( 'Fly In Right', 'amp' )
				},
				{
					value: 'fly-in-top',
					label: __( 'Fly In Top', 'amp' )
				},
				{
					value: 'pulse',
					label: __( 'Pulse', 'amp' )
				},
				{
					value: 'rotate-in-left',
					label: __( 'Rotate In Left', 'amp' )
				},
				{
					value: 'rotate-in-right',
					label: __( 'Rotate In Right', 'amp' )
				},
				{
					value: 'twirl-in',
					label: __( 'Twirl In', 'amp' )
				},
				{
					value: 'whoosh-in-left',
					label: __( 'Whoosh In Left', 'amp' )
				},
				{
					value: 'whoosh-in-right',
					label: __( 'Whoosh In Right', 'amp' )
				},
				{
					value: 'pan-left',
					label: __( 'Pan Left', 'amp' )
				},
				{
					value: 'pan-right',
					label: __( 'Pan Right', 'amp' )
				},
				{
					value: 'pan-down',
					label: __( 'Pan Down', 'amp' )
				},
				{
					value: 'pan-up',
					label: __( 'Pan Up', 'amp' )
				},
				{
					value: 'zoom-in',
					label: __( 'Zoom In', 'amp' )
				},
				{
					value: 'zoom-out',
					label: __( 'Zoom Out', 'amp' )
				}
			] }
			onChange={ value => ( setAttributes( { animationType: value } ) ) }
		/>,
		<RangeControl
			key='duration'
			label={ __( 'Duration (ms)', 'amp' ) }
			value={ attributes.animationDuration ? parseInt( attributes.animationDuration ) : '' }
			onChange={ ( value ) => {
				value = value + 'ms';
				setAttributes( { animationDuration: value } );
			} }
			min='0'
			max='5000'
			placeholder={ placeHolder }
			initialPosition={ placeHolder }
		/>,
		<RangeControl
			key='delay'
			label={ __( 'Delay (ms)', 'amp' ) }
			value={ parseInt( attributes.animationDelay ) }
			onChange={ ( value ) => {
				value = value + 'ms';
				setAttributes( { animationDelay: value } );
			} }
			min='0'
			max='5000'
		/>
	];
}
