<?php

namespace Swimmer\Models;

class Website extends AbstractModel implements ModelInterface
{
	protected $table = 'websites';
	public $fields = [
		'title' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'identifier' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'repository' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'url' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'debug' => [
            'type'     => 'int',
            'required' => true,
            'field'    => 'boolean'
        ]
    ];
}
