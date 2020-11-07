<?php

namespace Swimmer\Models;

interface ModelInterface
{
	/**
	 * @param array $filter
	 * @param array $sort
	 * @param array $limit
	 * @return array
	 */
	public function get(array $filter = [], array $sort = [], array $limit = []): array;

	/**
	 * @param array $data
	 * @return bool
	 */
	public function put(array $data): bool;

	/**
	 * @param array $data
	 * @param int $id
	 * @return bool
	 */
	public function post(int $id, array $data): bool;

	/**
	 * @param int $id
	 * @return bool
	 */
	public function delete(int $id): bool;
}
