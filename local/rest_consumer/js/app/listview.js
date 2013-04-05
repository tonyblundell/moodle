define(["backbone"], function(Backbone) {
    return Backbone.View.extend({
        template: _.template($('#id_template_list').html()),

        initialize: function() {
            this.collection.on('reset', this.render, this);
            this.collection.fetch({reset: true});
        },

        render: function() {
            this.$el.html(this.template({models: this.collection.models}));
            this.$el.listview('refresh');
            return this;
        }
    });
});
