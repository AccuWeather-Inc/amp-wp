/**
 * WordPress dependencies
 */
import { useContext, useEffect, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Panel } from '@wordpress/components';

/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { WordmarkLogo } from '../components/svg/wordmark-logo';
import { UnsavedChangesWarning } from '../components/unsaved-changes-warning';
import { Stepper } from './components/stepper';
import { Nav } from './components/nav';
import { Navigation } from './components/navigation-context-provider';

/**
 * Side effect wrapper for page component.
 *
 * @param {Object} props Component props.
 * @param {?any} props.children Component children.
 */
function PageComponentSideEffects( { children } ) {
	useEffect( () => {
		document.body.scrollTop = 0;
		document.documentElement.scrollTop = 0;
	}, [] );

	return children;
}

/**
 * Setup wizard root component.
 *
 * @param {Object} props Component props.
 * @param {string} props.closeLink Link to return to previous user location.
 * @param {string} props.finishLink Exit link.
 */
export function SetupWizard( { closeLink, finishLink } ) {
	const { activePageIndex, currentPage: { title, PageComponent, showTitle }, moveBack, moveForward, pages } = useContext( Navigation );

	const PageComponentWithSideEffects = useMemo( () => () => (
		<PageComponentSideEffects>
			<PageComponent />
		</PageComponentSideEffects>
	// eslint-disable-next-line react-hooks/exhaustive-deps
	), [ PageComponent ] );

	return (
		<div className="amp-settings-container">
			<div className="amp-settings">
				<div className="amp-stepper-container">
					<WordmarkLogo />
					<div className="amp-settings-plugin-name">
						{ __( 'Official AMP Plugin for WordPress', 'amp' ) }
					</div>
					<Stepper
						activePageIndex={ activePageIndex }
						pages={ pages }
					/>
				</div>
				<div className="amp-settings-panel-container">
					<Panel className="amp-settings-panel">
						{ false !== showTitle && (
							<h1>
								{ title }
							</h1>

						) }
						<PageComponentWithSideEffects />
					</Panel>
					<Nav
						activePageIndex={ activePageIndex }
						closeLink={ closeLink }
						finishLink={ finishLink }
						moveBack={ moveBack }
						moveForward={ moveForward }
						pages={ pages }
					/>
				</div>
			</div>
			<UnsavedChangesWarning />
		</div>
	);
}

SetupWizard.propTypes = {
	closeLink: PropTypes.string.isRequired,
	finishLink: PropTypes.string.isRequired,
};
