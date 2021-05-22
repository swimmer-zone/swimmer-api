<?php

namespace Swimmer\Models;

use Swimmer\Utils\Config;
use Swimmer\Utils\Getid3\Getid3;

class Album extends AbstractModel implements ModelInterface
{
	protected $table = 'albums';
    public $fields = [
        'title' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'cover' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'file'
        ],
        'comment' => [
            'type'     => 'text',
            'required' => false,
            'field'    => 'textarea'
        ],
        'artist' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ]
    ];

	/**
	 * @inheritDoc
	 */
	public function get(array $filter = [], array $sort = [], array $limit = []): array
	{
        $d = dir(Config::MEDIA_PATH);

        $projects = [];
        $albums = [];
        while (false !== ($entry = $d->read())) {

            if (!in_array($entry, ['.', '..']) && is_dir($d->path . '/' . $entry)) {
                $projects[] = $entry;
            }
        }
        $i = 0;

        foreach ($projects as $project) {
            $d = dir(Config::MEDIA_PATH . $project);

            while (false !== ($entry = $d->read())) {

                if (!in_array($entry, ['.', '..']) && is_dir($d->path . '/' . $entry)) {
                    $comment_file = Config::MEDIA_PATH . $project . '/' . $entry . '/intro.txt';

                    $albums[] = [
                        'id'          => ++$i,
                        'title'       => preg_replace('/\d+[- _]?(.+)/i', '$1', ucwords(str_replace('_', ' ', $entry))),
                        'title_lower' => $entry,
                        'cover'       => Config::MEDIA_URL . $entry . '/cover.jpg',
                        'comment'     => file_exists($comment_file) ? file_get_contents($comment_file) : $comment_file,
                        'artist'      => ucwords(str_replace('_', ' ', $project))
                    ];
                }
            }
        }

        sort($albums);

        return $albums;
	}

    /**
     * @inheritDoc
     */
    public function get_by_id(int $id): array
    {
        return $this->get()[$id - 1];
    }

	/**
	 * @inheritDoc
	 */
	public function put(array $data): bool
	{

	}

	/**
	 * @inheritDoc
	 */
	public function post(int $id, array $data): bool
	{

	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function delete(int $id): bool
	{

	}
}
