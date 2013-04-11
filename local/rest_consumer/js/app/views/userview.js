define(['jquery', 'backbone', 'text!app/views/templates/userview.html'],
function($, Backbone, tpl) {
    return Backbone.View.extend({
        template: _.template(tpl),

        render: function() {
            this.$el = $(this.template({model: this.model}));
            this.delegateEvents({
                'click #id_cancel': 'cancel',
                'click #id_delete': 'delete',
                'click #id_save': 'save'
            });
            this.$el.appendTo($.mobile.pageContainer);
            $.mobile.changePage(this.$el, {reverse: false, changeHash: false});
            return this;
        },

        cancel: function() {
            this.trigger('cancel');
        },

        delete: function() {
            this.model.destroy({
                success: _.bind(this.success, this),
                error: _.bind(this.error, this)
            });
        },

        save: function() {
            d = {};
            var self = this;
            _.each(['username', 'firstname', 'lastname', 'email'], function(field) {
                d[field] = self.$('#id_' + field).val();
            });
            this.model.save(d, {
                success: _.bind(this.success, this),
                error: _.bind(this.error, this)
            });
        },

        success: function(model, response, options) {
            if (model == this.model) {
                this.trigger('save');
            }
        },

        error: function(model, xhr, options) {
            if (model == this.model) {
                console.log(xhr);
                this.trigger('cancel');
            }
        }
    });
});
