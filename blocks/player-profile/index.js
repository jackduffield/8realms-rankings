import React from 'react';
import ReactDOM from 'react-dom';

const { registerBlockType } = wp.blocks;
const { InspectorControls, ServerSideRender } = wp.blockEditor;
const { TextControl } = wp.components;

const PlayerProfile = () => {
    return (
        <div>
            <h1>Player Profile</h1>
            {/* Your component code */}
        </div>
    );
};

document.addEventListener('DOMContentLoaded', () => {
    const element = document.getElementById('player-profile');
    if (element) {
        ReactDOM.render(<PlayerProfile />, element);
    }
});

registerBlockType('rankings/player-profile', {
    title: 'Player Profile',
    icon: 'admin-users',
    category: 'widgets',
    attributes: {
        player: {
            type: 'string',
            default: 'Jack Duffield'
        }
    },
    edit: ({ attributes, setAttributes }) => {
        const { player } = attributes;
        return (
            <>
                <InspectorControls>
                    <div className="player-profile-settings">
                        <TextControl
                            label="Player Name"
                            value={player}
                            onChange={(value) => setAttributes({ player: value })}
                        />
                    </div>
                </InspectorControls>
                <ServerSideRender
                    block="rankings/player-profile"
                    attributes={attributes}
                />
            </>
        );
    },
    save: () => {
        return null;
    }
});