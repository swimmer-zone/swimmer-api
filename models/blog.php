<?php

namespace Swimmer\Models;

class Blog extends AbstractModel implements ModelInterface
{
	protected $table = 'blogs';
    protected $fields = [
        'title' => [
            'type'     => 'varchar',
            'required' => true
        ],
        'body' => [
            'type'     => 'text',
            'required' => true
        ],
        'concept' => [
            'type'     => 'int',
            'required' => true
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
