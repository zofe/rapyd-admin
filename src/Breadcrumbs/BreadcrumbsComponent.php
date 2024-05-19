<?php


namespace Zofe\Rapyd\Breadcrumbs;

use Illuminate\Support\Collection;
use Illuminate\View\Component;

class BreadcrumbsComponent extends Component
{
    /**
     * @var Manager
     */
    public $breadcrumbs;

    /**
     * @var string|null
     */
    public $route;

    /**
     * @var mixed|null
     */
    public $parameters;

    /**
     * @var string|null
     */
    public $class;

    /**
     * @var string|null
     */
    public $active;

    /**
     * Create a new component instance.
     *
     * @param Manager     $manager
     * @param string|null $route
     * @param mixed|null  $parameters
     * @param string|null $class
     * @param string|null $active
     */
    public function __construct(
        Manager $manager,
        string $route = null,
        $parameters = null,
        string $class = "breadcrumb-item small",
        string $active = "active"
    ) {
        $this->breadcrumbs = $manager;
        $this->route = $route;
        $this->parameters = $parameters;
        $this->class = $class;
        $this->active = $active;
    }

    /**
     * @return Collection
     * @throws \Throwable
     */
    public function generate(): Collection
    {
        if ($this->route !== null) {
            return $this->breadcrumbs->generate($this->route, $this->parameters);
        }

        return $this->breadcrumbs->current($this->parameters);
    }

    /**
     * Determine if the component should be rendered.
     *
     * @return bool
     */
    public function shouldRender(): bool
    {
        return $this->breadcrumbs->has($this->route);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('rpd::components.breadcrumbs');
    }
}
