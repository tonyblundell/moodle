# Moodle Restful

## Introduction

Moodle REST web services are not *really* very RESTful and doesn't meet many (or any) of [these suggestions](http://devo.ps/blog/2013/03/22/designing-a-restful-api-that-doesn-t-suck.html). It is therefore impractical (or impossible) to build a RESTful JSON API that can be consumed by a JavaScript client built with a framework like [Backbone.js](http://backbonejs.org/).

This [local plugin](http://docs.moodle.org/dev/Local_plugins) attempts to rectify the above by using:

* [Silex](http://silex.sensiolabs.org/) to build a RESTful JSON API
* [Moodle's PHPUnit support](http://docs.moodle.org/dev/PHPUnit) for unit testing
* [Silex](http://silex.sensiolabs.org/doc/testing.html) and [Symfony](http://symfony.com/doc/current/book/testing.html) for web testing
* Moodle token authentication

## Install

* git clone [the repository](https://github.com/mikemcgowan/moodle)
* git checkout [the restful branch](https://github.com/mikemcgowan/moodle/tree/restful)
* install Moodle against the cloned codebase
* login as admin and set [Moodle debugging](http://docs.moodle.org/24/en/Debugging) to at least `MINIMAL` (optional) 
* in the `$CFG->dirroot` of the Moodle, have a look at `composer.json` to examine the dependencies
* in the `$CFG->dirroot` of the Moodle, add [Composer](http://getcomposer.org) with `curl -s http://getcomposer.org/installer | php`
* ensure Apache can read all the files in `/vendor` (e.g. with `chmod -R`)

## Tests

* follow the [Moodle doc](http://docs.moodle.org/dev/PHPUnit) to set up [PHPUnit](http://en.wikipedia.org/wiki/PHPUnit) for the Moodle
* set the following in `config.php`:

        $CFG->phpunit_prefix = 'phpu_';
        $CFG->phpunit_dataroot = '/home/example/phpu_moodledata';

* in the `$CFG->dirroot` of the Moodle, run `php admin/tool/phpunit/cli/init.php` (this will take a while to complete)
* execute the tests with:

        ./vendor/phpunit/phpunit/composer/bin/phpunit web_test local/rest_provider/tests/web_test.php

## Authentication

A Moodle mobile web service token (wstoken) can be obtained by requesting `/login/token.php?username=myuser&password=secret&service=moodle_mobile_app` relative to the `$CFG->wwwroot` of the Moodle.

The wstoken returned by Moodle in the JSON response (e.g. `1a9dfa2fdca9210615361f36e23dd0ff`) must be sent in each HTTP request in header `Authorization` and set to `Bearer 1a9dfa2fdca9210615361f36e23dd0ff`.

The user to whom the wstoken belongs will be set as the session user for the duration of the http request meaning that any functionality that depends on `$USER->id` will be executed in the context of the user making the HTTP request.
 
## RESTful JSON API

The RESTful JSON API was built with the following influences:

* [Django Tastypie](http://django-tastypie.readthedocs.org/en/latest/)
* [A blog post](http://devo.ps/blog/2013/03/22/designing-a-restful-api-that-doesn-t-suck.html)

It allows [CRUD](http://en.wikipedia.org/wiki/Create,_read,_update_and_delete) operations on Moodle users. (This is not particularly useful in itself, but it's *how* the plugin is implemented that's important, not what it does.)

### Routes

* `HTTP GET /user/current` returns user data about the Moodle user making the HTTP request
* `HTTP GET /user/{id}` returns user data about the given Moodle user
* `HTTP GET /user` returns all Moodle users
* `HTTP POST /user` creates a new Moodle user
* `HTTP PUT /user/{id}` updates the given Moodle user
* `HTTP DELETE /user/{id}` deletes the given Moodle user
