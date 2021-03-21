# Error Report 2 (Client)

An error reporting service for SPFW.
Clients send error and exception logs to an API.

## Requirements 

* SPFW >=1.0.0 and
* PHP >=7.4.

## Installation

* Include this git-project as submodule in your modules-directory,
* add ErrorReport2Client as Error-Exception-handler to your config.

__Example:__
```
cd src/modules
git submodule add URL_TO_REPOSITORY
```

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
