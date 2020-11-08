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

The following tables are created by default:
* blogs
* links
* templates
* websites

If you want to add any other tables, a new model has to be added. This can be done by adding the following code:

```
<?php

namespace Swimmer\Models;

class Test extends AbstractModel implements ModelInterface
{
    protected $table = 'tests';
    protected $fields = [
        'title' => [
            'type'     => 'varchar',
            'required' => true,
            'default'  => 'test'
        ],
        'body' => [
            'type'     => 'text',
            'required' => false
        ],
        'concept' => [
            'type'     => 'int',
            'required' => true,
            'default'  => 0
        ]
    ];
}
```

Where `tests` is the name of the table, the `$fields` array contains all the columns and the model name is the singular version of the table name, starting with a capital. For now, only field types `varchar`, `int`, `text` and `date` are supported.

After that, there are two steps left:
1. Add the namespace of the model to the constructor of `Swimmer\Controllers\Api`, like so: `$this->testModel = new \Swimmer\Models\Test;`
2. Add a method to `Swimmer\Controllers\Api` as below, optional parameters can be provided to the `get()` method to filter or sort the results

```
    /**
     * @see https://sww.tf/portfolio/
     * @return array
     */
    private function tests(): array
    {
        return $this->testModel->get();
    }
```

**Note: In the future I might add a migrate script to add columns to existing tables. For now, be sure to think your table structure through.**


## Todo

* Update CORS to be able to work with **react-admin**