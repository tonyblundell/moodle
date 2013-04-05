require.config({
    // external script aliases
    paths: {
        "jquery": "external/jquery-1.9.1",
        "jquerymobile": "external/jquery.mobile-1.3.0",
        "underscore": "external/underscore",
        "backbone": "external/backbone"
    },

    // Sets the configuration for your third party scripts that are not AMD compatible
    shim: {
        "backbone": {
            "deps": ["underscore", "jquery"],
            "exports": "Backbone"  // attaches "Backbone" to the window object
        }
    }
});

require(["jquery", "backbone"], function($, Backbone) {
    // Set up the "mobileinit" handler before requiring jQuery Mobile's module
    $(document).on("mobileinit", function() {
        // Prevents all anchor click handling including the addition of active button state and alternate link blurring
        $.mobile.linkBindingEnabled = false;

        // Disabling this will prevent jQuery Mobile from handling hash changes
        $.mobile.hashListeningEnabled = false;
    });

    // send wstoken with every XHR
    $(document).ajaxSend(function(event, xhr) {
        xhr.setRequestHeader('Authorization', 'Bearer ' + wstoken);
    });

    require(["jquerymobile"], function() {
        // empty
    });

    require(["app/abstractcollection", "app/listview"], function(AbstractCollection, ListView) {
        new ListView({
            el: '#id_listview',
            collection: new AbstractCollection()
        });
    });

});
