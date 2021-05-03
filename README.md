# Docker stack for virtualhosts-enabled web development

This collection of Docker Compose (actually only one) will help you to host several websites locally, each with individual `*.localhost` subdomain and specific Apache/PHP version and settings:

![](https://files.italic.fr/Sélection_03052021_0bfa.png)

⚠️ This stack has not been tested in live or even in staging environment. It is still work-in-progress, use it at your own risks. That's why it's not published on the Docker Hub yet.

## What's included:

The reverse proxy:
- https://hub.docker.com/r/jwilder/nginx-proxy/

The demo stack:
- https://hub.docker.com/_/httpd
- https://hub.docker.com/_/php
- https://github.com/mlocati/docker-php-extension-installer
- https://hub.docker.com/_/mariadb
- https://www.adminer.org/

The WGUI management interface:
- https://github.com/portainer/portainer


# 1. Installation

## 1.1. Install Docker

https://docs.docker.com/engine/install/

## 1.2. Download or clone to your server root or user directory (eg, `~/user/sites`)

```
git clone https://github.com/Germain-Italic/docker.git
cd docker
```

### 1.2.1. Launch reverse proxy

```
docker-compose -f nginx-proxy/docker-compose.yml up --build --detach
```

Default: you should see a 503 error on http://localhost/


### 1.2.2. Launch demo stack

**LAMP-Alp-2.4-10.4-7.4** is composed of:
- **L**inux **Alp**ine (latest)
- **A**pache 2.4 (latest)
- **M**ySQL using MariaDB 10.4
- **P**HP 7.4


First, rename `docker/LAMP-Alp-2.4-10.4-7.4/.env.dist` to `.env`, then build and launch:

```
mv docker/LAMP-Alp-2.4-10.4-7.4/.env.dist docker/LAMP-Alp-2.4-10.4-7.4/.env
docker-compose -f LAMP-Alp-2.4-10.4-7.4/docker-compose.yml up --build
```

Use `ctrl+C` to exit the process. Add `--detach` to have your shell back.


### 1.2.3. Edit your `hosts` file to route subdomain requests

**Mac / Linux**

```
# sudo nano /etc/hosts

# replce
127.0.0.1       localhost

# by
127.0.0.1       localhost *.localhost
```

**Windows**

Use [Hostsman](https://www.abelhadigital.com/hostsman/)


### 1.2.4. Check default results

- You should see a directory listing at http://lamp-alp-2.4-10.4-7.4.localhost/
- You should see a technical page at http://lamp-alp-2.4-10.4-7.4.localhost/docker-infos/ which looks like this:

![](https://files.italic.fr/Sélection_03052021_f8de.png)


### 1.2.5. Duplicate stack / add a virtual host

Duplicate and rename the demo project, change the variables, launch the new stack:

```
NEWPROJECT=myproject
echo $NEWPROJECT
cp -r LAMP-Alp-2.4-10.4-7.4/ ${NEWPROJECT}
sed -i "s/lamp-alp-2.4-10.4-7.4/${NEWPROJECT}/g" $NEWPROJECT/.env
docker-compose -f ${NEWPROJECT}/docker-compose.yml --env-file ${NEWPROJECT}/.env up --build --detach
```

If you are adding this project for the first time, add the external `${NEWPROJECT}_public` newtork to `nginx-proxy/docker-compose.yml`

⚠️ Be careful to avoid duplicate networks in `nginx-proxy/docker-compose.yml`!


```
printf '%s\n' '0?external: true?a' "  ${NEWPROJECT}_public:"$'\n    external: true' . x | ex nginx-proxy/docker-compose.yml
printf '%s\n' '$+?ports:?i' '      - '${NEWPROJECT}'_public' . x | ex nginx-proxy/docker-compose.yml
```

Rebuild and launch the proxy with the extra network:

```
docker-compose -f nginx-proxy/docker-compose.yml --env-file nginx-proxy/.env up --build --detach
```

### 1.2.6. Deleting a virtual host

If you just want to stop/delete the `${NEWPROJECT}` stack's containers without deleting the project files from your drive, skip this step.

If you wan to delete the project completely, you will have to tear down the reverse proxy **first** because it has a link to the `${NEWPROJECT}_public` external network.

If you remove the `${NEWPROJECT}` containers without stopping the reverse proxy, however the `${NEWPROJECT}_public` network won't be deleted.

To delete volumes, type `down -v` instead.

```
NEWPROJECT=myproject
docker-compose -f nginx-proxy/docker-compose.yml --env-file nginx-proxy/.env down
docker-compose -f ${NEWPROJECT}/docker-compose.yml --env-file ${NEWPROJECT}/.env down
docker-compose -f nginx-proxy/docker-compose.yml --env-file nginx-proxy/.env up --build --detach
```

---



# 2. Managing containers via CLI

## 2.1. Start / Stop

```
cd myproject

# keep the console open and show the logs
docker-compose up

# or

# hide the logs
docker-compose up -d

# rebuild containter
docker-compose up -d --build


# restart all services in container
docker-compose restart

# restart a single service
docker-compose restart db



# _Stops containers and removes containers, networks, volumes, and images created by up_
# from https://docs.docker.com/compose/reference/down/
docker-compose down
docker-compose down -v # Warning, will delete data

# stop a single service
docker-compose down apache
```

⚠️ _When starting `db` container instance with a data directory that already contains a database, the $MYSQL_ROOT_PASSWORD variable  will in any case be ignored, and the pre-existing database will not be changed in any way._


## 2.2. Get inside the containers

```
# load environment variables into current shell
# so you don't have to memorize PROJECT variable (default project is lamp-alp-2.4-10.4-7.4)
source .env

docker exec -it ${PROJECT}-apache /bin/ash
docker exec -it ${PROJECT}-php /bin/ash
docker exec -it ${PROJECT}-db /bin/bash
```

## 2.3. Connect to MySQL/MariaDB

### 2.3.1. From host (outside container)

Get the random port assigned to mysql image, in this case, internal mysql port 3306 is mapped to external port 49237 on ipv4 and ipv6

```
# full output
docker port ${PROJECT}-db
3306/tcp -> 0.0.0.0:49237
3306/tcp -> :::49237

# or, just the port
docker port ${PROJECT}-db  | rev | cut -d ":" -f1 | rev  | tac | sort -u -t: -k1,1
49237
```

Then:

```
mysql --port=49237 --protocol=tcp -u root -p${MYSQL_ROOT_PASSWORD}
```

OR, with a cool one-liner:

```
mysql --port=`docker port ${PROJECT}-db  | rev | cut -d ":" -f1 | rev  | tac | sort -u -t: -k1,1` --protocol=tcp -u root -p${MYSQL_ROOT_PASSWORD}

mysql: [Warning] Using a password on the command line interface can be insecure.
Welcome to the MySQL monitor.  Commands end with ; or \g.
Your MySQL connection id is 14
Server version: 5.5.5-10.4.18-MariaDB-1:10.4.18+maria~focal mariadb.org binary distribution

Copyright (c) 2000, 2021, Oracle and/or its affiliates.

Oracle is a registered trademark of Oracle Corporation and/or its
affiliates. Other names may be trademarks of their respective
owners.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

mysql>
```

### 2.3.2. From the container itself

```
docker exec -it ${PROJECT}-db /bin/bash -c "mysql -u root -p${MYSQL_ROOT_PASSWORD}"

Welcome to the MariaDB monitor.  Commands end with ; or \g.
Your MariaDB connection id is 15
Server version: 10.4.18-MariaDB-1:10.4.18+maria~focal mariadb.org binary distribution

Copyright (c) 2000, 2018, Oracle, MariaDB Corporation Ab and others.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

MariaDB [(none)]> 
```


**Show containers logs**

If you run with `docker-compose up -d` logs don't show up. Use the followings:

```
docker logs ${PROJECT}-apache
docker logs ${PROJECT}-php
docker logs ${PROJECT}-db
```


---


# 3. Managing containers with GUI

## 3.1. Via Portainer (preferred method)

_[Portainer](https://github.com/portainer/portainer) is a lightweight management UI which allows you to easily manage your different Docker environments_


```
docker-compose -f portainer/docker-compose.yml up --detach
```

Then go to http://localhost:9000/ and chose the Docker stack on setup.

It is simply a wrapper to the default installation command ([from the docs](https://documentation.portainer.io/v2.0/deploy/ceinstalldocker/)):


```
docker volume create portainer_data
docker run -d -p 9000:9000 --name=portainer --restart=always -v /var/run/docker.sock:/var/run/docker.sock -v portainer_data:/data portainer/portainer-ce
http://localhost:9000/#!/1/docker/containers
```

## 3.2. Alternative methods

- [https://dockstation.io/](DockStation) (GUI, cross platform)
- [https://github.com/jesseduffield/lazydocker](LazyDocker) (TUI, cross platform)



---



# 4. Customize configuration


## 4.1. Apache

Obtain the upstream default configuration from the container:

```
docker run --rm httpd:2.4 cat /usr/local/apache2/conf/httpd.conf > conf/httpd/httpd.conf.dist
```

- Rename httpd.conf.dist to httpd.conf if you want to reset your configuration to Apache's default.
- Edit httpd.conf.dist to suite your needs, exemple: try disabling directory listing.

_Default:_

![](https://files.italic.fr/Sélection_03052021_f478.png)

Edit the line `Options +Indexes` in `httpd.conf` (switch `+` with `-`).

Restart the container:

```
germain@xps:~/Sites/docker/LAMP-Alp-2.4-10.4-7.4$ docker-compose restart apache
Restarting lamp-alp-2.4-10.4-7.4-apache ... done
```
_After:_

![](https://files.italic.fr/Sélection_03052021_3bfa.png)


## 4.2. PHP

### 4.2.1. `php.ini` variables

Obtain the upstream default configuration from the container:

```
docker run --rm php:7.4-fpm cat /usr/local/etc/php/php.ini-production > conf/php/php.ini-production.dist
docker run --rm php:7.4-fpm cat /usr/local/etc/php/php.ini-development > conf/php/php.ini-development.dist
```

Rename php.ini.orig to php.ini if you want to reset your configuration to PHP's default.

_It is strongly recommended to use the production config for images used in production environments!_

You may want to tweak (default values):

```
upload_max_filesize = M
post_max_size = 8M
memory_limit = 128M
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
;date.timezone =
```

### 4.2.2. PHP extensions

Edit `/LAMP-Alp-2.4-10.4-7.4/conf/php/Dockerfile` to suite your needs.
For example, add `mysqli` to pass all tests on http://lamp-alp-2.4-10.4-7.4.localhost/docker-infos/

_Before:_

![](https://files.italic.fr/Sélection_03052021_9889.png)


```
# docker/LAMP-Alp-2.4-10.4-7.4/conf/php/Dockerfile

FROM php:7.4-fpm-alpine

RUN docker-php-ext-install \
        pdo_mysql \
        mysqli
```

Rebuild container

```
docker-compose up --build --detach php
```

_After:_

![](https://files.italic.fr/Sélection_03052021_347c.png)

---

# 5. Roadmap
- [ ] Adminer: always pull latest version
- [ ] Default page (http://localhost): create a welcome page that lists all available vhosts
- [ ] Test https w/ Let's Encrypt
- [ ] Create a virtualhost generator from template
- [ ] Enforce restricted visibility of DOCKER_ALIAS to external networks inside Apache configuration
- [ ] Avoid duplicate network names on project duplication

---

# 6. Resources

- https://docs.docker.com/compose/
- https://docs.docker.com/compose/networking/
- https://docs.docker.com/samples/wordpress/
- https://github.com/docker/awesome-compose
- https://w3blog.fr/2016/02/23/docker-securite-10-bonnes-pratiques/
- https://blog.gougousis.net/file-permissions-the-painful-side-of-docker/