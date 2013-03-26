# Moodle Icecream

## Introduction

It is difficult to write loosely coupled, elegant, maintainable, testable Moodle plugins. Moodle development tends to be freestyle, with each Moodle coder bringing their own idioms and coding styles to the table. In a large project with several contributors, this can become unwieldy. Moodle is very monolithic and tends not to have any separation of concerns. Business logic, database access, http request handling code and html templating are often tightly coupled together in one long, untestable PHP file.

This [local plugin](http://docs.moodle.org/dev/Local_plugins) attempts to rectify the above by using:

* [Silex](http://silex.sensiolabs.org/) for routing
* [Twig](http://twig.sensiolabs.org/) for templating
* [Moodle's PHPUnit support](http://docs.moodle.org/dev/PHPUnit) for unit testing
* [Silex](http://silex.sensiolabs.org/doc/testing.html) and [Symfony](http://symfony.com/doc/current/book/testing.html) for web testing

## Install

* git clone [the repository](https://github.com/mikemcgowan/moodle-icecream)
* install Moodle against the cloned codebase
* login as admin and set [Moodle debugging](http://docs.moodle.org/24/en/Debugging) to at least `MINIMAL` (optional) 
* in the `$CFG->dirroot` of the Moodle, have a look at `composer.json` to examine the dependencies
* run `./composer.phar update` to install the dependencies
* ensure Apache can read all the files in `/vendor` (e.g. with `chmod -R`)
* requesting `/local/icecream` should display a list of icecreams

## Tests

* follow the [Moodle doc](http://docs.moodle.org/dev/PHPUnit) to set up [PHPUnit](http://en.wikipedia.org/wiki/PHPUnit) for the Moodle
* set the following in `config.php`:

        $CFG->phpunit_prefix = 'phpu_';
        $CFG->phpunit_dataroot = '/home/example/phpu_moodledata';

* in the `$CFG->dirroot` of the Moodle, run `php admin/tool/phpunit/cli/init.php` (this will take a while to complete)
* execute the tests with:

        ./vendor/phpunit/phpunit/composer/bin/phpunit icecream_model_test local/icecream/tests/icecream_model_test.php
        ./vendor/phpunit/phpunit/composer/bin/phpunit web_test local/icecream/tests/web_test.php

## Standards

* Set Silex's debugging setting to Moodle's debugging setting with `$app['debug'] = debugging('', DEBUG_MINIMAL);`
* No HTTP request/response code outside `app.php`
* No database access code (i.e. no use of `global $DB`) outside models in `/models`
* No HTML templating outside [Twig](http://twig.sensiolabs.org/) templates in `/templates`
* Use [`UrlGeneratorServiceProvider`](http://silex.sensiolabs.org/doc/providers/url_generator.html) to generate URLs from named routes (rather than hardcoding relative URLs)
* Use [`SilexApplication::redirect`](http://silex.sensiolabs.org/api/Silex/Application.html#method_redirect) (rather than Moodle's `redirect()` which breaks web tests)
* Use [`SilexApplication::match`](http://silex.sensiolabs.org/api/Silex/Application.html#method_match) to define a single route for `GET` and `POST` for form handling
* `twiglib.php` is not plugin-specific and may be moved to `$CFG->dirroot` or `$CFG->libdir` if multiple plugins require it

## Routes

The [Silex app](http://silex.sensiolabs.org/documentation) defines the following [routes](http://silex.sensiolabs.org/doc/usage.html#routing) (relative to `/local/icecream`):

* `/` - publically visible, lists all icecreams
* `/manage` - landing page for admins to manage ([i.e. CRUD](http://en.wikipedia.org/wiki/Create,_read,_update_and_delete)) icecreams
* `/create` - admin page showing a form for creating a new icecream
* `/update` - admin page showing a form for updating an existing icecream
* `/delete` - admin route for deleting an existing icecream
* `/user` - user page showing a form for stating icecream preferences
