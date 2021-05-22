<?php

namespace Swimmer\Models;

class Link extends AbstractModel implements ModelInterface
{
	protected $table = 'links';
	public $fields = [
		'title' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'url' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'is_portfolio' => [
            'type'     => 'int',
            'required' => true,
            'field'    => 'boolean'
        ],
        'sort' => [
            'type'     => 'int',
            'required' => true,
            'field'    => 'number'
        ]
    ];
}
