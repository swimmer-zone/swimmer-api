<?php

namespace Swimmer\Models;

class Link extends AbstractModel implements ModelInterface
{
	protected $table = 'links';
	protected $fields = [
		'title' => [
            'type'     => 'varchar',
            'required' => true
        ],
        'url' => [
            'type'     => 'varchar',
            'required' => true
        ],
        'is_portfolio' => [
            'type'     => 'int',
            'required' => true
        ],
        'sort' => [
            'type'     => 'int',
            'required' => true
        ]
    ];
}
