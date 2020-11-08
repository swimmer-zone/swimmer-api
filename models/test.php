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
