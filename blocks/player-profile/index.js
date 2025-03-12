import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, ServerSideRender } from '@wordpress/block-editor';
import { TextControl } from '@wordpress/components';
import metadata from './block.json';

registerBlockType(metadata, {
    edit: (props) => {
        const { attributes: { player }, setAttributes } = props;

        const onChangePlayer = (newValue) => {
            setAttributes({ player: newValue });
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
        return null;
    },
});