# Swimmer API

This is a custom created API, migrated away from Laravel to be able to learn to create my own RESTful API.


## Configuration

* Copy `utils/config.php.example` to `utils/config.php` and enter your configuration details.
* Create a directory called `assets/images` to store the images belonging to your project.
* Create a directory called `assets/tracks` to store any audio tracks, there will be a JSON available of this through *[YOUR_URL]/tracks/[YOUR_ALBUM]* including ID3v1 tags
* There are 4 models readily available:
  * Blog
  * Link
  * Template - This can be used to store e-mail templates, in the body, use `%s` to display any string that is posted, in the same order as they appear in the form.
  * Website - This can be used as referer to link the output of the API to the identifier. This can also be used to set a debug status to a website. **NOTE: Only 1 website can have this status at a time**


## Add model

If you add any other tables, a new model has to be added. This can be done by adding the following code:

```
<?php

namespace Swimmer\Models;

class Link extends AbstractModel implements ModelInterface
{
	protected $table = 'links';
	protected $fields = ['id', 'title', 'url', 'is_portfolio', 'sort', 'created_at', 'updated_at'];
}
```

Where `links` is the name of the table, the `$fields` array contains all the columns and the model name is the singular version of the table name, starting with a capital.


## Tables

Run the following queries to create the tables:

### Blogs

```
CREATE TABLE `blogs` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `concept` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `blogs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
```

### Links
```
CREATE TABLE `links` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_portfolio` int(11) DEFAULT NULL,
  `sort` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `links`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `links`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
```

### Templates
```
CREATE TABLE `templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `css` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `to` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply_to` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fields` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `required_fields` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
```

### Websites
```
CREATE TABLE `websites` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `repository` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `debug` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `websites`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `websites`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
```


## Todo

* Add model for images
* Update CORS to be able to work with **react-admin**
* Add install script to create the tables automatically upon initialization
* Add migrate script to automatically create a table upon adding a model