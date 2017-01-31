![Mobizel](http://www.mobizel.com/wp-content/uploads/2013/04/logomobizel.png)

Au commencement...
------------------
Commencer par rechercher 'app_name', 'AppName' et 'APP_NAME' dans le projet et le remplacer par le nom de l'application

Quick Installation with Vagrant
-------------------------------

```bash
$ cd etc/vagrant
$ vagrant up
```

Quick Installation with Docker
------------------------------

```bash
$ cd etc/docker
$ docker-compose build
$ docker-compose up -d
$ docker exec -it $(docker-compose ps -q app_name_php) bash
$ composer install
$ bin/console app:install --no-interaction
```

The install script will give you the option to run fixtures that make testing and development phases much easier.

If you want to work on assets, please run the following commands:

```bash
$ npm install
$ gulp
```

[Behat](http://behat.org) scenarios
-----------------------------------

By default Behat uses `http://app_name.dev/app_test.php/` as your application base url. If your one is different,
you need to create `behat.yml` files that will overwrite it with your custom url:

```yaml
imports: ["behat.yml.dist"]

default:
    extensions:
        Behat\MinkExtension:
            base_url: http://my.custom.url
```

Then run selenium-server-standalone:

```bash
$ bin/selenium-server-standalone
```

Then setup your test database:

```bash
$ php bin/console doctrine:database:create --env=test
$ php bin/console doctrine:migrations:migrate --env=test
```

You can run Behat using the following commands:

```bash
$ bin/behat
```