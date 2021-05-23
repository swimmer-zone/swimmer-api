<?php

namespace Swimmer\Models;

class Template extends AbstractModel implements ModelInterface
{
	protected $table = 'templates';
	public $fields = [
		'title' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'subject' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'body' => [
            'type'     => 'text',
            'required' => true,
            'field'    => 'editor',
            'hide'     => true
        ],
        'css' => [
            'type'     => 'text',
            'required' => true,
            'field'    => 'textarea',
            'hide'     => true
        ],
        'to' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'reply_to' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'fields' => [
            'type'     => 'text',
            'required' => true,
            'field'    => 'text',
            'hide'     => true
        ],
        'required_fields' => [
            'type'     => 'text',
            'required' => true,
            'field'    => 'text',
            'hide'     => true
        ]
    ];
}
