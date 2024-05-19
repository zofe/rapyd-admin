<?php

namespace Zofe\Rapyd\Traits;

use Livewire\Features\SupportPagination\HandlesPagination;

trait WithPagination
{
    use HandlesPagination;

    public $perPage = 10;
    protected $paginationTheme = 'bootstrap';
}
