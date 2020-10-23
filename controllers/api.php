<?php

namespace Swimmer\Controllers;

/**
 * class Api
 * @author Swimmer 2020
 * @see https://sww.tf/
 */
class Api
{
    public $project;

    private $debug = false;
    private $debug_email = 'yupsie@gmail.com';
    private $sql;
    private $url_segments = [];

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
        $this->blogModel     = new \Swimmer\Models\Blog;
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
     * @return string
     */
    public function get()
    {
        $method = trim($this->url_segments[0], '/');
        $param = isset($this->url_segments[1]) ? trim($this->url_segments[1], '/') : false;

        if (method_exists($this, $method)) {
            if ($param) {
                $array = $this->{$method}($param);
            }
            else {
                $array = $this->{$method}();
            }
        }
        else {
            $array = $this->error();
        }
        return json_encode($array);
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
     * @see https://sww.tf/blogs/
     * @return array
     */
    private function blogs(): array
    {
        return $this->blogModel->get(['concept' => false]);
    }

    /**
     * @see https://sww.tf/blog/{title}
     * @param string $slug
     * @return array
     */
	private function blog(string $slug): array
	{
        return $this->blogModel->get_by_slug($slug);
	}

    /**
     * @see https://sww.tf/links/
     * @return array
     */
    private function links(): array
    {
        return $this->linkModel->get(['is_portfolio' => false]);
    }

    /**
     * @see https://sww.tf/portfolio/
     * @return array
     */
    private function portfolio(): array
    {
        return $this->linkModel->get(['is_portfolio' => true]);
    }

    /**
     * @see https://sww.tf/tracks/
     * @return array
     */
    private function tracks(): array
    {
        return $this->trackModel->get(['project' => $this->project]);
    }

    /**
     * @param array $request
     * @return array
     */
    public function post(array $request): array
    {
        $errors   = [];
        $messages = [];
        $template = array_shift($this->templateModel->get(['title' => $this->project]));
        $headers  = [
            'From: info@sww.tf',
            'Reply-To: %s',
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-type:text/html;charset=UTF-8'
        ];


        foreach (explode(',', $template['required_fields']) as $required_field) {
            if (!isset($request[$required_field]) || empty($request[$required_field])) {
                $messages[] = 'Not all required fields are filled';
                $errors[]   = $required_field;
            }
        }

        if (isset($request['email']) && !filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
            $messages[] = 'E-mail is not valid';
            $errors[]   = 'email';
        }

        foreach (explode(',', $template['fields']) as $field) {
            if (!isset($request[$field])) {
                $request[$field] = '';
            }
        }

        if (count($errors) < 1 && !mail(DEBUG ? DEBUG_MAIL : $template['to'], $template['subject'],
            vsprintf('<style type="text/css">' . $template['css'] . '</style>' . $template['body'], $request),
            sprintf(implode("\r\n", $headers), $request['email'] ?? $template['reply_to'])
        )) {
            return [
                'sent'    => false,
                'message' => 'An unknown error has occurred while sending the e-mail',
                'errors'  => $errors
            ];
        }

        if (count($errors) > 0) {
            return [
                'sent'    => false,
                'message' => implode(', ', array_unique($messages)),
                'errors'  => $errors
            ];
        }

        return [
            'sent' => true
        ];
    }
}
