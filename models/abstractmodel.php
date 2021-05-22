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
    protected $errors;
    public $fields = [];

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
            $output[] = $row;
        }
        return $output;
	}

	/**
	 * @inheritDoc
	 */
	public function get_by_id(int $id): array
	{
        return $this->get(['id' => $id])[0];
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

		$return = $this->sql->query($query);
		$this->errors[] = $this->sql->error;

		return $return;
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

		$return = $this->sql->query($query);
		$this->errors[] = $this->sql->error;

		return $return;
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

		$return = $this->sql->query($query);
		$this->errors[] = $this->sql->error;

		return $return;
	}

	/**
	 * @return bool
	 */
    public function create(): bool
    {
    	$query = "
			CREATE TABLE IF NOT EXISTS `" . $this->table . "` (
		";
		foreach ($this->get_all_fields() as $name => $data) {
			if (in_array($name, ['created_at', 'updated_at'])) continue;

			switch ($data['type']) {
				case 'int':
					$query .= "
						`" . $name ."` int(11) UNSIGNED";
					break;

				case 'varchar':
			  		$query .= "
			  			`" . $name ."` varchar(191) COLLATE utf8mb4_unicode_ci";
			  		break;

			  	case 'text':
			  		$query .= "
			  			`" . $name ."` mediumtext COLLATE utf8mb4_unicode_ci";
			  		break;

			  	case 'date':
			  		$query .= "
			  			`" . $name ."` date";
			  		break;
			}
			$query .= ($data['required'] ? " NOT NULL" : " NULL");
			if (!$data['required'] || isset($data['default'])) {
				$query .= " DEFAULT " . (isset($data['default']) ? "'" . $data['default'] . "'" : "NULL");
			}
			$query .= ",";
		}
		$query .= "
				`created_at` timestamp NULL DEFAULT current_timestamp(),
			  	`updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
		";
    	$query_alter_1 = "ALTER TABLE `" . $this->table . "` ADD PRIMARY KEY (`id`)";
    	$query_alter_2 = "ALTER TABLE `" . $this->table . "` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT";

		$result = $this->sql->query($query);
		$this->errors[] = $this->sql->error;

		$result_alter_1 = $this->sql->query($query_alter_1);
		$this->errors[] = $this->sql->error;

		$result_alter_2 = $this->sql->query($query_alter_2);
		$this->errors[] = $this->sql->error;

		return ($result && $result_alter_1 && $result_alter_2);
    }

    /**
     * @return array
     */
    public function get_errors()
    {
    	return $this->errors;
    }

	/**
	 * @return array
	 */
	private function get_all_fields()
	{
        $field_id = [
        	'id' => [
        		'type' 	   => 'int',
        		'required' => true
        	]
        ];
        $field_dates = [
        	'created_at' => [
        		'type'     => 'timestamp',
        		'required' => true
        	],
        	'updated_at' => [
        		'type'     => 'timestamp',
        		'required' => true
        	]
        ];

		$fields = array_merge($field_id, $this->fields, $field_dates);

        return $fields;
	}
}
