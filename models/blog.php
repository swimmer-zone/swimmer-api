<?php

namespace Swimmer\Models;

class Blog extends AbstractModel implements ModelInterface
{
	protected $table = 'blogs';
    protected $fields = ['id', 'title', 'body', 'concept', 'created_at', 'updated_at'];

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
