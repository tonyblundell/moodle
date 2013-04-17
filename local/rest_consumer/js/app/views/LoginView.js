define(['jquery', 'backbone', 'text!app/views/templates/LoginView.html'],
function($, Backbone, tpl) {
    return Backbone.View.extend({
        template: _.template(tpl),

        initialize: function(options) {
            this.incorrect = options.incorrect;
        },

        render: function() {
            this.$el = $(this.template({incorrect: this.incorrect}));
            this.delegateEvents({
                'click #id_ok': 'ok'
            });
            this.$el.appendTo($.mobile.pageContainer);
            $.mobile.changePage(this.$el, {reverse: false, changeHash: false});
            return this;
        },

        ok: function() {
            this.trigger('ok', this.$('#id_username').val(), this.$('#id_password').val());
        }
    });
});
