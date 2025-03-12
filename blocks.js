/**
 * WordPress Block Registration
 *
 * Necessary to register Gutenberg blocks for the tables in the plugin.
 */

import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, ServerSideRender } from '@wordpress/block-editor';
import { TextControl } from '@wordpress/components';
import { createElement } from '@wordpress/element';

// Register block for Player Profile
registerBlockType('rankings/player-profile', {
    title: 'Player Profile',
    icon: 'admin-users',
    category: 'widgets',
    attributes: {
        player: {
            type: 'string',
            default: 'Jack Duffield' // default player for preview/editor
        },
    },
    edit: ( props ) => {
        const { attributes: { player }, setAttributes } = props;

        const onChangePlayer = ( newValue ) => {
            setAttributes( { player: newValue } );
        };

        return (
            <>
                <InspectorControls>
                    <div className="player-profile-settings">
                        <TextControl
                            label="Player Name"
                            value={player}
                            onChange={onChangePlayer}
                        />
                    </div>
                </InspectorControls>
                <ServerSideRender
                    block="rankings/player-profile"
                    attributes={props.attributes}
                />
            </>
        );
    },
    save: () => {
        // Server side rendering
        return null;
    },
});

// Register block for Rankings Table
registerBlockType('rankings/rankings', {
    title: 'Rankings Table',
    icon: 'chart-bar',
    category: 'widgets',
    edit: () => {
        return <ServerSideRender block="rankings/rankings" />;
    },
    save: () => {
        return null;
    },
});

// Register block for Searchable Rankings Table
registerBlockType('rankings/searchable-rankings', {
    title: 'Searchable Rankings Table',
    icon: 'search',
    category: 'widgets',
    edit: () => {
        return <ServerSideRender block="rankings/searchable-rankings" />;
    },
    save: () => {
        return null;
    },
});

// Register block for Events List
registerBlockType('rankings/events', {
    title: 'Events List',
    icon: 'calendar-alt',
    category: 'widgets',
    edit: () => {
        return <ServerSideRender block="rankings/events" />;
    },
    save: () => {
        return null;
    },
});

// Register block for Faction Rankings
registerBlockType('rankings/faction-rankings', {
    title: 'Faction Rankings',
    icon: 'awards',
    category: 'widgets',
    edit: () => {
        return <ServerSideRender block="rankings/faction-rankings" />;
    },
    save: () => {
        return null;
    },
});
