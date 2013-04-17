define(['backbone'],
function(Backbone) {
    return Backbone.Model.extend({
        initialize: function(options) {
            this.username = options.username;
            this.password = options.password;
        },

        urlRoot: function() {
            return loginUrl + encodeURI('?username=' + this.username + '&password=' + this.password + '&service=moodle_mobile_app');
        }
    });
});
