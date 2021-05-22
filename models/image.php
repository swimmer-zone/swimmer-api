<?php

namespace Swimmer\Models;

use Swimmer\Utils\Config;
use Swimmer\Utils\Exif\Exif;

class Image extends AbstractModel implements ModelInterface
{
	protected $table = 'images';
    public $fields = [
        'title' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'file'
        ]
    ];

    /**
     * @inheritDoc
     */
    public function get(array $filter = [], array $sort = [], array $limit = []): array
    {
        $images = [];
        $exif = new Exif;

        if (isset($filter['directory'])) {
            $dir = $filter['directory'];
        } else {
            $dir = Config::IMAGE_PATH;
        }

        $d = dir($dir);
        while (false !== $entry = $d->read()) {
            if (!in_array($entry, ['.', '..'])) {
                if (is_dir($dir . '/' . $entry)) {
                    $images[Config::IMAGE_URL . '/' . $dir . '/' . $entry] = [
                        'type' => 'directory',
                        'contents' => $this->get(['directory' => $dir . '/' . $entry], $sort, $limit)
                    ];
                }
                else{
                    $images[Config::IMAGE_URL . '/' . $dir . '/' . $entry] = [
                        'type' => mime_content_type($dir . '/' . $entry),
                        'name' => $entry,
                        'gps'  => $exif->get_gps_data($dir . '/' . $entry),
                        'exif' => $exif->get_exif_info($dir . '/' . $entry)
                    ];
                }
            }
        }
        $d->close();

        ksort($images);
       
        return $images;
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
