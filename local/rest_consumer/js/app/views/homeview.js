define(['jquery', 'backbone', 'text!app/views/templates/homeview.html'],
function($, Backbone, tpl) {
    return Backbone.View.extend({
        template: _.template(tpl),

        render: function() {
            this.$el = $(this.template({models: this.collection.models}));
            this.$el.appendTo($.mobile.pageContainer);
            $.mobile.changePage(this.$el, {reverse: false, changeHash: false});
            return this;
        }
    });
});
