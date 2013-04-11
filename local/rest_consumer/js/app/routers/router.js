define(['jquery', 'backbone', 'app/models/abstractmodel', 'app/collections/abstractcollection', 'app/views/homeview', 'app/views/userview'],
function($, Backbone, AbstractModel, AbstractCollection, HomeView, UserView) {
    return Backbone.Router.extend({
        initialize: function() {
            this.userCollection = new AbstractCollection();
            this.first = true;
            this.fetchUserCollection();
            this.views = [];
            Backbone.history.start({silent: true});
        },

        home: function() {
            var view = new HomeView({
                collection: this.userCollection
            });
            this.registerView(view.render());
        },

        fetchUserCollection: function() {
            this.userCollection.fetch({
                success: _.bind(this.success, this),
                error: _.bind(this.error, this)
            });
        },

        user: function(id) {
            var model = typeof id == 'undefined' ? new AbstractModel() : this.userCollection.get(id);
            var view = new UserView({
                model: model
            });
            this.listenToOnce(view, 'cancel', _.bind(this.navigate, this, '', {trigger: true}));
            this.listenToOnce(view, 'save', this.fetchUserCollection);
            this.registerView(view.render());
        },

        registerView: function(view) {
            this.views.push(view);
            while (this.views.length > 2) {
                this.views.shift().remove();
            }
        },

        success: function(collection, response, options) {
            if (collection == this.userCollection) {
                if (this.first) {
                    this.first = false;
                    this.home();
                } else {
                    this.navigate('', {trigger: true});
                }
            }
        },

        error: function(collection, response, options) {
            if (collection == this.userCollection) {
                console.log(response);
            }
        }
    });
});
