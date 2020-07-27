/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { createContext, useState } from '@wordpress/element';

export const Options = createContext();

/**
 * MOCK.
 *
 * @param {Object} props
 * @param {any} props.children Component children.
 */
export function OptionsContextProvider( { children } ) {
	const [ updates, updateOptions ] = useState( {} );
	const [ originalOptions, setOriginalOptions ] = useState( {
		mobile_redirect: true,
		theme_support: 'some-support',
	} );

	return (
		<Options.Provider value={
			{
				editedOptions: { ...originalOptions, ...updates },
				originalOptions,
				setOriginalOptions,
				updates,
				updateOptions: ( ( newOptions ) => {
					updateOptions( { ...updates, newOptions } );
				} ),
			}
		}>
			{ children }
		</Options.Provider>
	);
}
OptionsContextProvider.propTypes = {
	children: PropTypes.any,
};
