<?php

namespace Swimmer\Models;

class Website extends AbstractModel implements ModelInterface
{
	protected $table = 'websites';
	protected $fields = [
		'title' => [
            'type'     => 'varchar',
            'required' => true
        ],
        'identifier' => [
            'type'     => 'varchar',
            'required' => true
        ],
        'repository' => [
            'type'     => 'varchar',
            'required' => true
        ],
        'url' => [
            'type'     => 'varchar',
            'required' => true
        ],
        'debug' => [
            'type'     => 'int',
            'required' => true
        ]
    ];
}
