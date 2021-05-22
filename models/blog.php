<?php

namespace Swimmer\Models;

class Blog extends AbstractModel implements ModelInterface
{
	protected $table = 'blogs';
    public $fields = [
        'title' => [
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
        'concept' => [
            'type'     => 'int',
            'required' => true,
            'field'    => 'boolean'
        ]
    ];

	/**
	 * @param string $slug
	 * @return array
	 */
	public function get_by_slug(string $slug): array
	{
        $query = "
            SELECT      id
            FROM        blogs
            WHERE       LOWER(REPLACE(title, ' ', '-')) = '" . $this->sql->escape_string($slug) . "'
        ";
        $result = $this->sql->query($query);

        while ($row = $result->fetch_assoc()) {
            return $this->get_by_id((int)$row['id']);
        }

        return [];
	}
}
