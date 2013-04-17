require.config({
    // external script aliases
    paths: {
        'text': 'external/text',
        'jquery': 'external/jquery-1.9.1',
        'jquerymobile': 'external/jquery.mobile-1.3.1',
        'underscore': 'external/underscore',
        'backbone': 'external/backbone'
    },

    // Sets the configuration for your third party scripts that are not AMD compatible
    shim: {
        'backbone': {
            'deps': ['underscore', 'jquery'],
            'exports': 'Backbone'  // attaches 'Backbone' to the window object
        }
    }
});

require(['jquery', 'backbone', 'app/routers/Router'],
function($, Backbone, Router) {
    // set up the 'mobileinit' handler before requiring jQuery Mobile's module
    $(document).on('mobileinit', function() {
        // prevent all anchor click handling including the addition of active button state and alternate link blurring
        $.mobile.linkBindingEnabled = false;

        // disabling this will prevent jQuery Mobile from handling hash changes
        $.mobile.hashListeningEnabled = false;

        // disable other things as per http://coenraets.org/blog/2012/03/using-backbone-js-with-jquery-mobile/
        $.mobile.ajaxEnabled = false;
        $.mobile.pushStateEnabled = false;

        // disable other things as per http://addyosmani.github.io/backbone-fundamentals/#backbone-jquery-mobile
//        $.mobile.autoInitializePage = false;
        $.mobile.page.prototype.options.domCache = false;

        // enable some things as per http://addyosmani.github.io/backbone-fundamentals/#backbone-jquery-mobile
        $.mobile.phonegapNavigationEnabled = true;
        $.mobile.page.prototype.options.degradeInputs.date = true;
    });

    // workaround for jQuery balking at empty responses in POST, PATCH, DELETE
    $.ajaxSetup({dataFilter: function(data, type) {
        if (type == 'json' && data == '') {
            data = null;
        }
        return data;
    }});

    // when jQM is available, initialize the router
    require(['jquerymobile'], function() {
        new Router({routes: {
            '': 'login',
            'home': 'home',
            'user/:id': 'user',
            'user': 'user'
        }});
    });

});
