<?php

namespace Ddys\Laravel\Http\Controllers;

use Ddys\Laravel\PageService;
use Illuminate\Http\Request;

class PageController
{
    public function __construct(protected PageService $pages) {}

    public function front(Request $request)
    {
        return $this->view($request);
    }

    public function view(Request $request)
    {
        $ddysView = (string) $request->route('ddysView', 'latest');
        $slug = (string) $request->route('slug', '');
        $id = (string) $request->route('id', '');
        $username = (string) $request->route('username', '');
        $view = $this->pages->normaliseView($ddysView);
        $params = $request->only(['type', 'genre', 'region', 'year', 'sort', 'page', 'per_page', 'limit', 'q', 'month']);

        if ($slug !== '') {
            $params['slug'] = $slug;
        }
        if ($id !== '') {
            $params['id'] = $id;
        }
        if ($username !== '') {
            $params['username'] = $username;
        }

        return view('ddys::page', [
            'title' => $this->pages->title($view),
            'view' => $view,
            'tabs' => $this->pages->tabs($view),
            'content' => $this->pages->render($view, $params),
        ]);
    }
}
