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

    private $blogModel;
    private $linkModel;
    private $templateModel;
    private $trackModel;
    private $websiteModel;

    /**
     * Contains: scheme, host, path, query, fragment
     * @param array $url
     * @return string
     */
    public function __construct($url, $referer)
    {
        $this->albumModel    = new \Swimmer\Models\Album;
        $this->blogModel     = new \Swimmer\Models\Blog;
        $this->imageModel    = new \Swimmer\Models\Image;
        $this->linkModel     = new \Swimmer\Models\Link;
        $this->templateModel = new \Swimmer\Models\Template;
        $this->trackModel    = new \Swimmer\Models\Track;
        $this->websiteModel  = new \Swimmer\Models\Website;


        $url = parse_url($url);
        $this->url_segments  = explode('/', ltrim($url['path'], '/'));

        if ($referer && strpos($referer, 'localhost') === false) {
            $website = array_shift($this->websiteModel->get(['url' => $referer]));
        }
        else {
            $website = array_shift($this->websiteModel->get(['debug' => true]));
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
        $fields = $this->fields;

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
     * @see https://sww.tf/albums/
     * @see https://sww.tf/albums/{id}/
     * @param int $id
     * @return array
     */
    private function albums(?int $id = null): array
    {
        $this->fields = $this->albumModel->fields;
        if ($id) {
            return $this->albumModel->get_by_id($id);
        }
        return $this->albumModel->get();
    }

    /**
     * @see https://sww.tf/blogs/
     * @see https://sww.tf/blogs/{id}/
     * @param int $id
     * @return array
     */
    private function blogs(?int $id = null): array
    {
        $this->fields = $this->blogModel->fields;
        if ($id) {
            return $this->blogModel->get_by_id($id);
        }
        $blogs = $this->blogModel->get();
        $output = [];
        foreach ($blogs as $blog) {
            $blog['concept'] = ($blog['concept'] == 1);
            $blog['created_at'] = date('m/d/Y', strtotime($blog['created_at']));
            $blog['updated_at'] = date('m/d/Y', strtotime($blog['updated_at']));
            $output[] = $blog;
        }
        return $output;
    }

    /**
     * @see https://sww.tf/images/
     * @return array
     */
    private function images(): array
    {
        $this->fields = $this->imageModel->fields;
        return $this->imageModel->get();
    }

    /**
     * @see https://sww.tf/links/
     * @see https://sww.tf/links/{id}/
     * @param int $id
     * @return array
     */
    private function links(?int $id = null): array
    {
        $this->fields = $this->linkModel->fields;
        if ($id) {
            return $this->linkModel->get_by_id($id);
        }
        $links = $this->linkModel->get();
        $output = [];
        foreach ($links as $link) {
            $link['is_portfolio'] = ($link['is_portfolio'] == 1);
            $link['created_at'] = date('m/d/Y', strtotime($link['created_at']));
            $link['updated_at'] = date('m/d/Y', strtotime($link['updated_at']));
            $output[] = $link;
        }
        return $output;
    }

    /**
     * @see https://sww.tf/templates/
     * @see https://sww.tf/templates/{id}/
     * @param int $id
     * @return array
     */
    private function templates(?int $id = null): array
    {
        $this->fields = $this->templateModel->fields;
        if ($id) {
            return $this->templateModel->get_by_id($id);
        }
        $templates = $this->templateModel->get();
        $output = [];
        foreach ($templates as $template) {
            $template['created_at'] = date('m/d/Y', strtotime($template['created_at']));
            $template['updated_at'] = date('m/d/Y', strtotime($template['updated_at']));
            $output[] = $template;
        }
        return $output;
    }

    /**
     * @see https://sww.tf/tracks/
     * @return array
     */
    private function tracks(): array
    {
        $this->fields = $this->trackModel->fields;
        return $this->trackModel->get_list();
    }

    /**
     * @see https://sww.tf/websites/
     * @see https://sww.tf/websites/{id}/
     * @param int $id
     * @return array
     */
    private function websites(?int $id = null): array
    {
        $this->fields = $this->websiteModel->fields;
        if ($id) {
            return $this->websiteModel->get_by_id($id);
        }
        $websites = $this->websiteModel->get();
        $output = [];
        foreach ($websites as $website) {
            $website['debug'] = ($website['debug'] == 1);
            $website['created_at'] = date('m/d/Y', strtotime($website['created_at']));
            $website['updated_at'] = date('m/d/Y', strtotime($website['updated_at']));
            $output[] = $website;
        }
        return $output;
    }

    private function get_menu()
    {
        $items = [];
        while (false !== ($entry = $d->read())) {

            if (!in_array($entry, ['.', '..', 'abstractmodel.php', 'modelinterface.php']) && is_dir($d->path . '/' . $entry)) {
                $items[] = pathinfo($entry, PATHINFO_FILENAME);
            }
        }

        sort($items);
    }
}
