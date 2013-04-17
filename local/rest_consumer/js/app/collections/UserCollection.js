define(['backbone', 'app/models/UserModel'],
function(Backbone, UserModel) {
    return Backbone.Collection.extend({
        model: UserModel,
        url: apiUrl
    });
});
