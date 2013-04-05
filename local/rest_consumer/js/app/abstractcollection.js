define(["backbone", "app/abstractmodel"], function(Backbone, AbstractModel) {
    return Backbone.Collection.extend({
        model: AbstractModel,
        url: apiUrl
    });
});
