/**
 * WordPress Block Registration
 *
 * Necessary to register Gutenberg blocks for the tables in the plugin.
 */
( function( blocks, element, components ) {
    var el = element.createElement;
    var ServerSideRender = components.ServerSideRender;

    // Register block for Player Profile
    blocks.registerBlockType('rankings/player-profile', {
        title: 'Player Profile',
        icon: 'admin-users',
        category: 'widgets',
        edit: function() {
            return el(ServerSideRender, {
                block: 'rankings/player-profile',
            });
        },        
        save: function() {
            return null;
        },
    });

    // Register block for Rankings Table
    blocks.registerBlockType('rankings/rankings', {
        title: 'Rankings Table',
        icon: 'chart-bar',
        category: 'widgets',
        edit: function() {
            return el(ServerSideRender, {
                block: 'rankings/rankings',
            });
        },
        save: function() {
            return null;
        },
    });

    // Register block for Searchable Rankings Table
    blocks.registerBlockType('rankings/searchable-rankings', {
        title: 'Searchable Rankings Table',
        icon: 'search',
        category: 'widgets',
        edit: function() {
            return el(ServerSideRender, {
                block: 'rankings/searchable-rankings',
            });
        },
        save: function() {
            return null;
        },
    });

    // Register block for Events List
    blocks.registerBlockType('rankings/events', {
        title: 'Events List',
        icon: 'calendar-alt',
        category: 'widgets',
        edit: function() {
            return el(ServerSideRender, {
                block: 'rankings/events',
            });
        },
        save: function() {
            return null;
        },
    });

    // Register block for Faction Rankings
    blocks.registerBlockType('rankings/faction-rankings', {
        title: 'Faction Rankings',
        icon: 'awards',
        category: 'widgets',
        edit: function() {
            return el(ServerSideRender, {
                block: 'rankings/faction-rankings',
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
