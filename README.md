# To make it easier for developers we've created a preinstalled version of the Material Dashboard for Symfony
This is a bootstrap for the Material Dashboard for Symfony composer package
[Dashboard bundle for symfony using Material Dashboard by Creative Tim][Mdfs]
## Features
- Preconfigured User class, Login and Registration forms
- Preconfigured Material Dashboard for Symfony with notifications enabled
## Installation
```sh
$ git clone git@github.com:WebBamboo/material-dashboard-symfony-installed.git YourDashboardName
$ cd YourDashboardName
$ composer install
# Copy your .env file then configure the DATABASE_URL
$ cp .env .env.local;nano .env.local
$ php bin/console doctrine:database:create
$ php bin/console doctrine:schema:update --force
# Optional Step: Configure the Username and Password in your UserFixture in order to create a user with ROLE_SUPER_ADMIN
# If you go with this step it is VERY IMPORTANT to remove the fixture after loading you don't want your plain-text password sticking around
$ nano src/DataFixtures/UserFixtures.php
$ php bin/console doctrine:fixtures:load
$ rm src/DataFixtures/UserFixtures.php

# Install assets
$ php bin/console assets:install

# At this point you are ready, you can run your app
$ symfony serve
```


[//]: #
   [Mdfs]: <https://github.com/WebBamboo/material-dashboard-symfony>

