define(['backbone', 'app/models/abstractmodel'],
function(Backbone, AbstractModel) {
    return Backbone.Collection.extend({
        model: AbstractModel,
        url: apiUrl
    });
});
