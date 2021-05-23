<?php

namespace Swimmer\Cp\Controllers;

use Swimmer\Utils\Config;

/**
 * class Admin
 * @author Swimmer 2020
 * @see https://sww.tf/
 */
class Admin
{
    public $project;

    private $sql;
    private $url_segments = [];
    private $fields;
    private $models = [];

    /**
     * Contains: scheme, host, path, query, fragment
     * @param array $url
     * @return string
     */
    public function __construct($url, $referer)
    {
        $this->models = [
            'albums'    => new \Swimmer\Models\Album,
            'blogs'     => new \Swimmer\Models\Blog,
            'images'    => new \Swimmer\Models\Image,
            'links'     => new \Swimmer\Models\Link,
            'templates' => new \Swimmer\Models\Template,
            'tracks'    => new \Swimmer\Models\Track,
            'websites'  => new \Swimmer\Models\Website
        ];

        $url = parse_url($url);
        $this->url_segments  = explode('/', ltrim($url['path'], '/'));

        if ($referer && strpos($referer, 'localhost') === false) {
            $website = array_shift($this->models['websites']->get(['url' => $referer]));
        }
        else {
            $website = array_shift($this->models['websites']->get(['debug' => true]));
        }

        $this->project = $website['identifier'];
    }

    /**
     * @return void
     */
    public function get()
    {
        $method = trim($this->url_segments[1], '/');
        $param  = isset($this->url_segments[2]) ? trim($this->url_segments[2], '/') : false;
        $action = isset($this->url_segments[3]) ? trim($this->url_segments[3], '/') : 'show';
        $menu   = array_keys($this->models);

        if ($method == 'errors') {
            $action = 'errors';
        }
        elseif (method_exists($this, $method)) {
            if ($param) {
                $table = $this->{$method}($param);
            }
            else {
                $table = $this->{$method}();
            }
        }
        else {
            return $this->error();
        }
        $fields = $this->models[$method]->fields;

        header('Content-TYpe: text/html');
        require('cp/views/index.php');
    }

    /**
     * @param array $request
     * @return string
     */
    public function post($request)
    {
        // insert
        return json_encode(['success' => true]);
    }

    /**
     * @param array $request
     * @return string
     */
    public function put($request)
    {
        // update
        return json_encode(['success' => true]);
    }

    /**
     * @return array
     */
    private function error(): array
    {
        header('HTTP/1.0 404 Not Found');
        return ['Error 404'];
    }

    /**
     * @see https://sww.tf/create/{table}/
     * @return array
     */
    private function create($table): array
    {
        $model     = $table . 'Model';
        $namespace = '\\Swimmer\\Models\\' . ucfirst($table);

        $this->$model = new $namespace;
        
        if ($this->$model->create()) {
            return ['Table created successfully'];
        }
        return ['Table creation failed' => $this->$model->get_errors()];
    }


    /**
     * @see https://sww.tf/cp/albums/
     * @param int $id
     * @return array
     */
    private function albums(?int $id = null): array
    {
        if ($id) {
            return $this->models['albums']->get_by_id($id);
        }
        return $this->models['albums']->get();
    }

    /**
     * @see https://sww.tf/cp/blogs/
     * @param int $id
     * @return array
     */
    private function blogs(?int $id = null): array
    {
        if ($id) {
            return $this->models['blogs']->get_by_id($id);
        }
        $blogs = $this->models['blogs']->get();
        $output = [];
        foreach ($blogs as $blog) {
            $blog['concept'] = ($blog['concept'] == 1);
            $output[] = $blog;
        }
        return $output;
    }

    /**
     * @see https://sww.tf/cp/images/
     * @return array
     */
    private function images(): array
    {
        return $this->models['images']->get();
    }

    /**
     * @see https://sww.tf/cp/links/
     * @param int $id
     * @return array
     */
    private function links(?int $id = null): array
    {
        if ($id) {
            return $this->models['links']->get_by_id($id);
        }
        $links = $this->models['links']->get();
        $output = [];
        foreach ($links as $link) {
            $link['is_portfolio'] = ($link['is_portfolio'] == 1);
            $output[] = $link;
        }
        return $output;
    }

    /**
     * @see https://sww.tf/cp/templates/
     * @param int $id
     * @return array
     */
    private function templates(?int $id = null): array
    {
        if ($id) {
            return $this->models['templates']->get_by_id($id);
        }
        $templates = $this->models['templates']->get();
        $output = [];
        foreach ($templates as $template) {
            $output[] = $template;
        }
        return $output;
    }

    /**
     * @see https://sww.tf/cp/tracks/
     * @return array
     */
    private function tracks(): array
    {
        return $this->models['tracks']->get_list();
    }

    /**
     * @see https://sww.tf/cp/websites/
     * @param int $id
     * @return array
     */
    private function websites(?int $id = null): array
    {
        if ($id) {
            return $this->models['websites']->get_by_id($id);
        }
        $websites = $this->models['websites']->get();
        $output = [];
        foreach ($websites as $website) {
            $website['debug'] = ($website['debug'] == 1);
            $output[] = $website;
        }
        return $output;
    }
}
