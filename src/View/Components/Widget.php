<?php

namespace Ddys\Laravel\View\Components;

use Ddys\Laravel\Renderer;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Widget extends Component
{
    public function __construct(
        public string $view = 'latest',
        public array $params = []
    ) {}

    public function render(): View
    {
        return view('ddys::components.widget', [
            'html' => app(Renderer::class)->render($this->view, $this->params),
        ]);
    }
}

