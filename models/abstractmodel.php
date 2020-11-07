<?php

namespace Swimmer\Models;

use Swimmer\Utils\Config;

abstract class AbstractModel
{
	protected $sql;
    protected $db = [
        'host' => Config::DB_HOST,
        'user' => Config::DB_USER,
        'pass' => Config::DB_PASS,
        'db'   => Config::DB_NAME,
        'url'  => Config::DB_LINK
    ];

	/**
	 * @inheritDoc
	 */
	public function __construct()
	{
        $this->sql = new \mysqli(
            $this->db['host'],
            $this->db['user'],
            $this->db['pass'],
            $this->db['db']
        );
        if ($this->sql->connect_error) {
            header('HTTP/1.1 500 Internal Server Error');
        }
	}

	/**
	 * @param string $table
	 * @param array $filter
	 * @param array $sort
	 * @param array $limit
	 * @return array
	 */
	public function get(array $filter = [], array $sort = [], array $limit = []): array
	{
        $output = [];

        $query = "
            SELECT      *,
                        DATE_FORMAT(created_at, '%d-%m-%Y') AS created_at,
                        DATE_FORMAT(updated_at, '%d-%m-%Y') AS updated_at
            FROM        `" . $this->table . "`
            WHERE 		1 = 1
        ";
        foreach ($filter as $column => $value) {
        	if (is_bool($value)) {
        		$value = $value ? 1 : 0;
        	}
        	if (is_numeric($value)) {
	            $query .= "
	            	AND `" . $column . "` = " . $value . "
	            ";
	        }
	        else {
	            $query .= "
	            	AND `" . $column . "` = '" . $this->sql->escape_string($value) . "'
	            ";
	        }
        }
        $query .= "
            ORDER BY    id DESC
        ";
        $result = $this->sql->query($query);

        while ($row = $result->fetch_assoc()) {
        	$row = array_map('utf8_decode', $row);
            $output[$row['id']] = $row;
        }
        return $output;
	}

	/**
	 * @inheritDoc
	 */
	public function get_by_id(int $id): array
	{
        return $this->get(['id' => $id])[$id];
	}

	/**
	 * @inheritDoc
	 */
	public function put(array $data): bool
	{
		$columns = [];

		foreach ($data as $column => $value) {
			$columns[] .= "`" . $column . "` = '" . $this->sql->escape_string($value) . "'";
		}
		$query = "
			INSERT INTO `" . $this->table . "` 
			SET 		" . implode(', ', $columns) . "
		";

		return $this->sql->query($query);
	}

	/**
	 * @inheritDoc
	 */
	public function post(int $id, array $data): bool
	{
		$columns = [];

		foreach ($data as $column => $value) {
			$columns[] .= "`" . $column . "` = '" . $this->sql->escape_string($value) . "'";
		}
		$query = "
			UPDATE 		`" . $this->table . "` 
			SET 		" . implode(', ', $columns) . "
			WHERE 		id = " . $id . "
		";

		return $this->sql->query($query);
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function delete(int $id): bool
	{
		$query = "
			DELETE FROM `" . $this->table . "` 
			WHERE 		id = " . $id . "
		";

		return $this->sql->query($query);
	}
}
