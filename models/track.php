<?php

namespace Swimmer\Models;

use Swimmer\Utils\Config;
use Swimmer\Utils\Getid3\Getid3;

class Track extends AbstractModel implements ModelInterface
{
	protected $table = 'tracks';

	/**
	 * @inheritDoc
	 */
	public function get(array $filter = [], array $sort = [], array $limit = []): array
	{
        $tracks_per_album = [];
        $getid3 = new Getid3;

        if ($filter['project'] == 'swimmer') {
            $albums = ['swimmer/collectifest', 'swimmer/the_beach', 'swimmer/the_pool'];
        }
        else {
            $albums = [$filter['project']];
        }
        $album_comments = [
            'swimmer/the_pool'      => 'These are the top 20 tracks I picked from the 80 tracks released under my previous alias.', 
            'swimmer/collectifest'  => 'We organised a festival for a small group of people to be able to show our talents. I created a set of some of my favorite tracks, where it was a necessity to be able to control it while on stage. I plan to make more of them in the future, using more recent tracks or maybe more like some sort of soundscape. I hope you enjoy it!', 
            'swimmer/the_beach'     => 'I started creating music when I was about 12 years old, somehow I came up with the name Yupsie and I kept using that name for quite a while. I started with FastTracker and later on I started using Propellerhead\'s Reason. Nowadays I use Ableton. Recently I decided to change my name to Swimmer and these are the tracks I actually created with my new alias. There are many more to come, so check back regularly!'
        ];

        foreach ($albums as $album) {
            $d = dir(Config::MEDIA_PATH . $album);

            $tracks = [];
            while (false !== ($entry = $d->read())) {

                if (!in_array($entry, array('.', '..'))) {

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

            $tracks_per_album[$album] = [
                'tracks'      => $tracks,
                'title'       => ucwords(str_replace(['swimmer/', '_'], ['', ' '], $album)),
                'title_lower' => $album,
                'cover'       => Config::MEDIA_URL . $album . '/cover.jpg',
                'comment'     => $album_comments[$album] ?? ''
            ];
        }

        return $tracks_per_album;
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
