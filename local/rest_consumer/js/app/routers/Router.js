define(['jquery', 'backbone', 'app/models/LoginModel', 'app/models/UserModel', 'app/collections/UserCollection', 'app/views/LoginView', 'app/views/HomeView', 'app/views/UserView'],
function($, Backbone, LoginModel, UserModel, UserCollection, LoginView, HomeView, UserView) {
    return Backbone.Router.extend({
        initialize: function() {
            this.userCollection = new UserCollection();
            this.views = [];
            Backbone.history.start();
        },

        login: function(options) {
            options = typeof options == 'undefined' ? {} : options;
            var view = new LoginView({incorrect: typeof options.incorrect == 'undefined' ? false : options.incorrect});
            this.listenToOnce(view, 'ok', this.attemptLogin);
            this.registerView(view.render());
        },

        home: function() {
            var view = new HomeView({
                collection: this.userCollection
            });
            this.registerView(view.render());
        },

        attemptLogin: function(username, password) {
            this.loginModel = new LoginModel({
                username: username,
                password: password
            });
            this.loginModel.fetch({
                success: _.bind(this.success, this),
                error: _.bind(this.error, this)
            });
        },

        fetchUserCollection: function() {
            this.userCollection.fetch({
                success: _.bind(this.success, this),
                error: _.bind(this.error, this)
            });
        },

        user: function(id) {
            var model = typeof id == 'undefined' ? new UserModel() : this.userCollection.get(id);
            var view = new UserView({
                model: model
            });
            this.listenToOnce(view, 'cancel', _.bind(this.navigate, this, 'home', {trigger: true}));
            this.listenToOnce(view, 'save', this.fetchUserCollection);
            this.registerView(view.render());
        },

        registerView: function(view) {
            this.views.push(view);
            while (this.views.length > 2) {
                this.views.shift().remove();
            }
        },

        success: function(obj, response, options) {
            if (obj == this.userCollection) {
                this.navigate('home', {trigger: true});
                return;
            }

            if (obj == this.loginModel) {
                // see whether credentials were okay
                if (this.loginModel.get('error')) {
                    this.login({incorrect: true});
                    return;
                }

                // send wstoken with every XHR
                var self = this;
                $(document).ajaxSend(function(event, xhr) {
                    xhr.setRequestHeader('Authorization', 'Bearer ' + self.loginModel.get('token'));
                });

                // fetch users
                this.fetchUserCollection();
                return;
            }
        },

        error: function(obj, response, options) {
            console.log(response);
        }
    });
});
