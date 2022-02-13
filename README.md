# Error Report 2 (Client)

An error reporting service for SPFW.
Clients send error and exception logs to an API.

## Requirements 

* SPFW >=2.0.0 (since ER2 Client 2.1.x) and
* PHP >=7.4.

If you are using SPFW 1.x.x check out ER2 Client Version 2.0.x.

## Installation

* Include this git-project as submodule in your modules-directory,
* add ErrorReport2Client as Error-Exception-handler to your config.

__Example:__
```
cd src/modules
git submodule add URL_TO_REPOSITORY
```

## Update
When ER2 is included as git submodule, update the submodule.
Future updates may change configuration options.
We try to keep ER2 backward compatible as much as reasonable.
Please read the release notes carefully for each release!

ER2 uses semantic versioning.
Breaking changes can be expected at major releases.

## Configuration

### Required configuration

The constructor requires two arguments:
* The api-server URI (full path) and
* the API token.

API token must be generated in advanced on the server side.

__Example:__
``$config->addErrorExceptionAction(new \ErrorReport2\ErrorReport2Client('https://localhost/er2_listener', '9318560345'), -1);``

### Optional configuration

#### Application identifier

If you are logging more than one service on the serverside, it is recommended to set up a service identifier.
This can be done by adding the service identifier as the third argument to the constructor.

__Example:__
`` new ErrorReport2Client('https://localhost/er2_listener', '9318560345', 'My wonderful web app'); ``

#### Limit Logged Data

By default, all data is sent to the API.
There are good reasons to limit the transmitted data, e.g. for security reasons.

##### Restrict Request Parameter

Request parameter contain all get- and post-variables of a request.
This can contain login form data, such as passwords.
These information should not be logged.
ER2 must be properly configured to minimize critical data for the transmission.

__Examples:__
```
$er2->blockCookieParameter('login_cookie');
$er2->blockGetParameter('session_id');
$er2->blockPostParameter('password');
$er2->blockSessionParameter('session_token');
```

##### Disable Certain Data Categories

While it is important to log error and exception details, it can be wished to stop logging other information than that.
Following data categories can be disabled:
* Environment variables (like $_SERVER),
* database queries,
* cookies,
* session ($_SESSION),
* get- ($_GET) and
* post variables ($_POST).

```
$er2->disableTransmittingCookies();
$er2->disableTransmittingDatabaseQueries();
$er2->disableTransmittingEnvironmentVariables();
$er2->disableTransmittingGetParameter();
$er2->disableTransmittingPostParameter();
$er2->disableTransmittingSessionVariables();
```

#### Server Request Timeout

If server API does not respond, ER2 will continue trying to reach the server.
PHP's default request timeout is 60 seconds.
This blocks clients from continuing, e.g. displaying the user a proper error page.
To limit the waiting time for the user, a shorter timeout than PHP's default timeout is recommended.
ER2 limits requests to 5 seconds by default.

This example reduces the timeout to 3 seconds:

`` $er2->setTimeout(3); ``

