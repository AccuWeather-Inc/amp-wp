/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { create } from 'react-test-renderer';
import { describe, expect, it, jest } from '@jest/globals';

/**
 * Internal dependencies
 */
import { TemplateModeOption } from '..';
import { READER, STANDARD, TRANSITIONAL } from '../../../common/constants';
import { OptionsContextProvider } from '../../options-context-provider';

jest.mock('../../../components/options-context-provider');

describe('TemplateModeOption', () => {
	it('matches snapshot', () => {
		let wrapper = create(
			<OptionsContextProvider>
				<TemplateModeOption
					mode={STANDARD}
					details="Standard info"
					detailsUrl="https://amp-wp.org/documentation/getting-started/standard/"
				>
					<div>{'Component children'}</div>
				</TemplateModeOption>
			</OptionsContextProvider>
		);
		expect(wrapper.toJSON()).toMatchSnapshot();

		wrapper = create(
			<OptionsContextProvider>
				<TemplateModeOption
					mode={READER}
					previouslySelected={true}
					details="Reader info"
					detailsUrl="https://amp-wp.org/documentation/getting-started/reader/"
				/>
			</OptionsContextProvider>
		);
		expect(wrapper.toJSON()).toMatchSnapshot();

		wrapper = create(
			<OptionsContextProvider>
				<TemplateModeOption
					details="Component details"
					detailsUrl="https://amp-wp.org/documentation/getting-started/reader/"
					mode={READER}
					previouslySelected={true}
					initialOpen={true}
					labelExtra={<div>{'Extra label content'}</div>}
				/>
			</OptionsContextProvider>
		);
		expect(wrapper.toJSON()).toMatchSnapshot();
	});

	it('is open by default if is current mode', () => {
		// Reader is the default in mock options context provider.
		const { container } = render(
			<OptionsContextProvider>
				<TemplateModeOption
					mode={READER}
					details="Reader info"
					detailsUrl="https://amp-wp.org/documentation/getting-started/reader/"
				>
					<div id="reader-mode-children">{'children'}</div>
				</TemplateModeOption>
				<TemplateModeOption
					mode={STANDARD}
					details="Standard info"
					detailsUrl="https://amp-wp.org/documentation/getting-started/standard/"
				>
					<div id="standard-mode-children">{'children'}</div>
				</TemplateModeOption>
			</OptionsContextProvider>
		);

		expect(container.querySelector('#reader-mode-children')).not.toBeNull();
		expect(container.querySelector('#standard-mode-children')).toBeNull();
	});

	it('is open by default if initialOpen is true', () => {
		// Reader is the default in mock options context provider.
		const { container } = render(
			<OptionsContextProvider>
				<TemplateModeOption
					mode={TRANSITIONAL}
					details="Transitional info"
					detailsUrl="https://amp-wp.org/documentation/getting-started/transitional/"
				>
					<div id="reader-mode-children">{'children'}</div>
				</TemplateModeOption>
				<TemplateModeOption
					mode={STANDARD}
					initialOpen={true}
					details="Standard info"
					detailsUrl="https://amp-wp.org/documentation/getting-started/standard/"
				>
					<div id="standard-mode-children">{'children'}</div>
				</TemplateModeOption>
			</OptionsContextProvider>
		);

		expect(
			container.querySelector('#transitional-mode-children')
		).toBeNull();
		expect(
			container.querySelector('#standard-mode-children')
		).not.toBeNull();
	});

	it('can be open', () => {
		const { container } = render(
			<OptionsContextProvider>
				<TemplateModeOption
					mode={TRANSITIONAL}
					initialOpen={true}
					details="Transitional info"
					detailsUrl="https://amp-wp.org/documentation/getting-started/transitional/"
				>
					<div id="transitional-mode-children">{'children'}</div>
				</TemplateModeOption>
			</OptionsContextProvider>
		);

		expect(
			container.querySelector('#transitional-mode-children')
		).not.toBeNull();
	});

	it('can be closed', () => {
		const { container } = render(
			<OptionsContextProvider>
				<TemplateModeOption
					mode={TRANSITIONAL}
					initialOpen={false}
					details="Transitional info"
					detailsUrl="https://amp-wp.org/documentation/getting-started/transitional/"
				>
					<div id="transitional-mode-children">{'children'}</div>
				</TemplateModeOption>
			</OptionsContextProvider>
		);

		expect(
			container.querySelector('#transitional-mode-children')
		).toBeNull();
	});
});
