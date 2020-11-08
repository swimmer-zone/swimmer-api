<?php

namespace Swimmer\Models;

class Template extends AbstractModel implements ModelInterface
{
	protected $table = 'templates';
	protected $fields = [
		'title' => [
            'type'     => 'varchar',
            'required' => true
        ],
        'subject' => [
            'type'     => 'varchar',
            'required' => true
        ],
        'body' => [
            'type'     => 'text',
            'required' => true
        ],
        'css' => [
            'type'     => 'text',
            'required' => true
        ],
        'to' => [
            'type'     => 'varchar',
            'required' => true
        ],
        'reply_to' => [
            'type'     => 'varchar',
            'required' => true
        ],
        'fields' => [
            'type'     => 'text',
            'required' => true
        ],
        'required_fields' => [
            'type'     => 'text',
            'required' => true
        ]
    ];
}
