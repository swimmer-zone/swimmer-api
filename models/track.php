<?php

namespace Swimmer\Models;

use Swimmer\Utils\Config;
use Swimmer\Utils\Getid3\Getid3;

class Track extends AbstractModel implements ModelInterface
{
	protected $table = 'tracks';
    public $fields = [
        'filename' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'file'
        ],
        'playtime_string' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text',
            'hide'     => true
        ],
        'playtime_seconds' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'title' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'file' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text',
            'hide'     => true
        ],
        'sample_rate' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text',
            'hide'     => true
        ],
        'tracknumber' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'artist' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'bpm' => [
            'type'     => 'int',
            'required' => true,
            'field'    => 'number'
        ],
        'albumartist' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text',
            'hide'     => true
        ],
        'album' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'genre' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text'
        ],
        'comment' => [
            'type'     => 'text',
            'required' => true,
            'field'    => 'textarea'
        ],
        'date' => [
            'type'     => 'varchar',
            'required' => true,
            'field'    => 'text',
            'hide'     => true
        ]
    ];

	/**
	 * @inheritDoc
	 */
	public function get(array $filter = [], array $sort = [], array $limit = []): array
	{
        $tracks_per_album = [];
        $getid3 = new Getid3;

        $d = dir(Config::MEDIA_PATH . $filter['project']);

        $albums = [];
        while (false !== ($entry = $d->read())) {

            if (!in_array($entry, ['.', '..']) && is_dir($d->path . '/' . $entry)) {
                $albums[] = $filter['project'] . '/' . $entry;
            }
        }

        sort($albums);

        foreach ($albums as $album) {
            $d = dir(Config::MEDIA_PATH . $album);

            $tracks = [];
            while (false !== ($entry = $d->read())) {

                if (!in_array($entry, ['.', '..'])) {

                    $getid3->analyze($d->path . '/' . $entry);
                    if (isset($getid3->info['tags']['id3v1'])) {
                        $tags = $getid3->info['tags']['id3v1'];

                        $tracks[Config::API_URL . $getid3->filename] = [
                            'filename'         => Config::API_URL . $getid3->filename,
                            'playtime_string'  => $getid3->info['playtime_string'],
                            'playtime_seconds' => round($getid3->info['playtime_seconds']),
                            'title'            => $tags['title'][0] ?? '',
                            'file'             => $entry,
                            'sample_rate'      => $getid3->info['audio']['sample_rate'] ?? '',
                            'tracknumber'      => $tags['track_number'][0] ?? 0,
                            'artist'           => $tags['artist'][0] ?? '',
                            'bpm'              => $tags['bpm'][0] ?? 0,
                            'albumartist'      => $tags['albumartist'][0] ?? '',
                            'album'            => $tags['album'][0] ?? '',
                            'genre'            => $tags['genre'][0] ?? '',
                            'comment'          => $tags['comment'][0] ?? '',
                            'date'             => $tags['date'][0] ?? ''
                        ];
                    }
                }
            }
            $d->close();

            ksort($tracks);

            $comment_file = Config::MEDIA_PATH . $album . '/intro.txt';

            $tracks_per_album[$album] = [
                'tracks'      => $tracks,
                'title'       => ucwords(substr(str_replace(['swimmer/', '_'], ['', ' '], $album), 3)),
                'title_lower' => $album,
                'cover'       => Config::MEDIA_URL . $album . '/cover.jpg',
                'comment'     => file_exists($comment_file) ? file_get_contents($comment_file) : $comment_file
            ];
        }

        return $tracks_per_album;
	}

    /**
     * @inheritDoc
     */
    public function get_list(array $filter = [], array $sort = [], array $limit = []): array
    {
        $getid3 = new Getid3;
        $d = dir(Config::MEDIA_PATH);
        $i = 0;

        $projects = [];
        $albums   = [];
        $tracks   = [];
        while (false !== ($entry = $d->read())) {

            if (!in_array($entry, array('.', '..')) && is_dir($d->path . '/' . $entry)) {
                $projects[] = $entry;
            }
        }

        foreach ($projects as $project) {
            $d = dir(Config::MEDIA_PATH . $project);

            while (false !== ($entry = $d->read())) {

                if (!in_array($entry, array('.', '..')) && is_dir($d->path . '/' . $entry)) {
                    $comment_file = Config::MEDIA_PATH . $project . '/' . $entry . '/intro.txt';

                    $albums[] = [
                        'title'       => preg_replace('/\d+[- _]?(.+)/i', '$1', ucwords(str_replace('_', ' ', $entry))),
                        'title_lower' => $entry,
                        'cover'       => Config::MEDIA_URL . $entry . '/cover.jpg',
                        'comment'     => file_exists($comment_file) ? file_get_contents($comment_file) : $comment_file,
                        'artist'      => ucwords(str_replace('_', ' ', $project))
                    ];
                }
            }
        }

        foreach ($albums as $album) {
            $d = dir(Config::MEDIA_PATH . strtolower($album['artist']) . '/' . $album['title_lower']);

            while (false !== ($entry = $d->read())) {

                if (!in_array($entry, array('.', '..'))) {

                    $getid3->analyze($d->path . '/' . $entry);
                    if (isset($getid3->info['tags']['id3v1'])) {
                        $tags = $getid3->info['tags']['id3v1'];

                        $tracks[Config::API_URL . $getid3->filename] = [
                            'id'          => ++$i,
                            'filename'         => Config::API_URL . $getid3->filename,
                            'playtime_string'  => $getid3->info['playtime_string'],
                            'playtime_seconds' => round($getid3->info['playtime_seconds']),
                            'title'            => $tags['title'][0] ?? '',
                            'file'             => $entry,
                            'sample_rate'      => $getid3->info['audio']['sample_rate'] ?? '',
                            'tracknumber'      => $tags['track_number'][0] ?? 0,
                            'artist'           => $tags['artist'][0] ?? '',
                            'bpm'              => $tags['bpm'][0] ?? 0,
                            'albumartist'      => $album['artist'],
                            'album'            => $tags['album'][0] ?? '',
                            'genre'            => $tags['genre'][0] ?? '',
                            'comment'          => $tags['comment'][0] ?? '',
                            'date'             => $tags['date'][0] ?? ''
                        ];
                    }
                }
            }
            $d->close();
        }

        sort($tracks);

        return $tracks;
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
