# Getting started

```
make start
```

This is a headless application which has a Drupal Contenta backend driving a Polymer app frontent.

| Drupal Contenta | Polymer App |
| -------------  |-------------|
| http://localhost | http://localhost:81 |


# Initialize new Drupal site.

The container doesn't automatically run `drush site-install` because we want it to be possible to stop and start an existing container without rebuilding the site every time. Instead, you need to manually run this after starting the container the first time:

`docker exec contentadocker_php_1 init-drupal`

_In the above command, "contentadocker_php_1" is the name of the running container for the php service. If docker-compose names yours differently, you can find it with `docker-compose ps`._

When it finishes successsfully, the command will output a one-time login URL.

# Restore existing Drupal site.

If you have backup files `./volumes/data.tgz` and `./volumes/www.tgz` then you can restore an existing drupal site.

```
sh ./volumes/restore.sh
```

# Backup current Drupal site

If you want to take a snapshot of the current Drupal site.

```
sh ./volumes/backup.sh
```


# Persistence

Persistent state is stored in two docker volumes (named `data` and `www`). You can destroy and recreate the containers as much as you like and your site will be preserved until you also destroy these volumes.
