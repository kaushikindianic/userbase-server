# UserBase

## Brought to you by the LinkORB Engineering team

## Install dependencies

    composer install
    bower install

## Initializing the database

    vendor/bin/dbtk-schema-loader schema:load app/schema.xml mysql://username:password@localhost/userbase

## Configuration

    cp app/config/parameters.yml.dist app/config/parameters.yml

Now edit the `app/config/parameters.yml` file to your preferences.

### userbase:

* `baseurl`: Base URL on which the service is available. I.e. http://localhost:8888
* `salt`: This Salt is used in hash calculations. For example for e-mail verification urls. It needs to be unique per installation for security purposes.
* `postfix`: Indication of full username postfix. For example "@yourservice.web". This is only used in for UI clarifications.
* `logourl`: URL to the (square) logo image of your service.
* `layout`: Basepath to the layout directory. Can be relative (i.e. `layout/default`) or absolute.
* `strings`: An array of filenames containing strings. The array is loaded in order, which can be used to override standard strings. In general, it's good to load `app/strings.yml` first, then optionally override with further filenames.

### pdo (database):

Setup the pdo connection details (dsn, username, password) in the `pdo` section.

### herald:

If you're using Herald as a Mailer, configure it in the `herald` section of the `config.yml`

## Starting the server

    php -S 0.0.0.0:8888 -t web/

Now open this link in your browser: [http://127.0.0.1:8888](http://127.0.0.1)

## Setup - OAuth server

1. Install database `app/schema.xml`
2. Create an oauth-client

    ```sql
    INSERT INTO oauth_clients (client_id, client_secret, redirect_uri) VALUES ("testclient", "testpass", "http://fake/");
    ```
3. Use any OAuth2 client
    1. localhost:8888/oauth2/authorize?response_type=code&client_id=testclient&state=xyz
    2. curl -u testclient:testpass http://localhost:8888/oauth2/code -d 'grant_type=authorization_code&code=72a1c6741a650ea6950fa1b9898ce3fd4bac1a51' -v
    3. curl http://localhost:8888/oauth2/api -d 'access_token=4da361a665cbf4fd81ce1271bcf2f12c1158b9a6' -v
