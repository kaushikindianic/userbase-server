# UserBase

## Install dependencies

    composer install
    
## Initializing the database

    vendor/bin/database-manager database:loadschema userbase app/schema.xml --apply

## Configuration

    cp config.yml.dist config.yml
    
Now edit the `config.yml` file to your preferences.

### userbase:

* `dbname`: Name of the database config file in `/share/config/database/{x}.ini (loaded by linkorb/database-manager)
* `baseurl`: Base URL on which the service is available. I.e. http://localhost:8888
* `salt`: This Salt is used in hash calculations. For example for e-mail verifiction urls. It needs to be unique per installation for security purposes.
* `postfix`: Indication of full username postfix. For example "@yourservice.web". This is only used in for UI clarifications.
* `logourl`: URL to the (square) logo image of your service.
* `layout`: Basepath to the layout directory. Can be relative (i.e. `layout/default`) or absolute.
* `strings`: An array of filenames containing strings. The array is loaded in order, which can be used to override standard strings. In general, it's good to load `app/strings.yml` first, then optionally override with further filenames.

### herald:

If you're using Herald as a Mailer, configure it in the `herald` section of the `config.yml`

## Starting the server

    php -S 0.0.0.0:8888

Now open this link in your browser: [http://127.0.0.1:8888](http://127.0.0.1)
