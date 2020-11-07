<?php

namespace Swimmer\Models;

use Swimmer\Utils\Config;
use Swimmer\Utils\Exif\Exif;

class Image extends AbstractModel implements ModelInterface
{
	protected $table = 'images';

	/**
	 * @inheritDoc
	 */
	public function get(array $filter = [], array $sort = [], array $limit = []): array
	{
        $exif = new Exif;

        if (isset($filter['directory'])) {
            $d = dir($filter['directory']);
        } else {
            $d = dir(Config::IMAGE_PATH);
        }

        $images = [];
        while (false !== ($entry = $d->read())) {

            if (!in_array($entry, array('.', '..'))) {
                $path = Config::IMAGE_PATH . $entry;

                if (is_dir($_SERVER['DOCUMENT_ROOT'] . '/../www/' . $path)) {
                    $images[Config::IMAGE_URL . $entry] = [
                        'type' => 'directory',
                        'content' => $this->get(['directory' => $path], $sort, $limit)
                    ];
                }
                else {
                    $images[Config::IMAGE_URL . $entry] = [
                        'type' => mime_content_type($_SERVER['DOCUMENT_ROOT'] . '/../www/' . $path),
                        // 'gps'  => $exif->get_gps_data($path),
                        // 'exif' => $exif->get_exif_info($path)
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
