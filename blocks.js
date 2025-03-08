/**
 * WordPress Block Registration
 *
 * Necessary to register Gutenberg blocks for the tables in the plugin.
 */
( function( blocks, element, components ) {
    var el = element.createElement;
    var ServerSideRender = components.ServerSideRender;

    // Register block for Player Profile
    blocks.registerBlockType('data-display/player-profile', {
        title: 'Player Profile',
        icon: 'admin-users',
        category: 'widgets',
        edit: function() {
            return el(ServerSideRender, {
                block: 'data-display/player-profile',
            });
        },        
        save: function() {
            return null;
        },
    });

    // Register block for Rankings Table
    blocks.registerBlockType('data-display/rankings', {
        title: 'Rankings Table',
        icon: 'chart-bar',
        category: 'widgets',
        edit: function() {
            return el(ServerSideRender, {
                block: 'data-display/rankings',
            });
        },
        save: function() {
            return null;
        },
    });

    // Register block for Searchable Rankings Table
    blocks.registerBlockType('data-display/searchable-rankings', {
        title: 'Searchable Rankings Table',
        icon: 'search',
        category: 'widgets',
        edit: function() {
            return el(ServerSideRender, {
                block: 'data-display/searchable-rankings',
            });
        },
        save: function() {
            return null;
        },
    });

    // Register block for Events List
    blocks.registerBlockType('data-display/events', {
        title: 'Events List',
        icon: 'calendar-alt',
        category: 'widgets',
        edit: function() {
            return el(ServerSideRender, {
                block: 'data-display/events',
            });
        },
        save: function() {
            return null;
        },
    });

    // Register block for Faction Rankings
    blocks.registerBlockType('data-display/faction-rankings', {
        title: 'Faction Rankings',
        icon: 'awards',
        category: 'widgets',
        edit: function() {
            return el(ServerSideRender, {
                block: 'data-display/faction-rankings',
            });
        },
        save: function() {
            return null;
        },
    });

} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.components
);
