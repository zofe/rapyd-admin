<?php

namespace App\Modules\Log\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Zofe\Rapyd\Traits\WithDataTable;
use Zofe\Rapyd\Modules\Log\Services\LogParser;

class LogAppTable extends Component
{
    use Authorize, WithDataTable;

    public string $search = '';

    public string $level = '';

    public array $logFiles = [];

    public string $logFile = '';

    public ?array $stack = null;

    public function booted(): void
    {
        $this->authorize('admin|view everything|view logs');
    }

    public function mount(): void
    {
        $this->logFiles = $this->parser()->files();
        $this->logFile = $this->logFiles[0] ?? '';
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'level', 'logFile'])) {
            $this->resetPage();
        }
    }

    // The table only carries headers; the stack is fetched when asked for.
    public function showStack(string $id): void
    {
        $this->stack = $this->parser()->entry($this->logFile, $id);
        $this->dispatch('show-modal', ['logStack']);
    }

    protected function parser(): LogParser
    {
        return new LogParser();
    }

    protected function entries(): array
    {
        $entries = $this->logFile ? $this->parser()->entries($this->logFile) : [];

        if ($this->level !== '') {
            $entries = array_filter($entries, fn ($e) => $e['level'] === $this->level);
        }
        if ($this->search !== '') {
            $s = strtolower($this->search);
            $entries = array_filter($entries, fn ($e) => str_contains(strtolower($e['text']), $s)
                || str_contains(strtolower($e['stack']), $s)
                || str_contains(strtolower($e['context']), $s));
        }

        return array_values($entries);
    }

    public function render()
    {
        $entries = $this->entries();
        $perPage = (int) config('rapyd.log.app.per_page', 50);
        $page = $this->getPage();
        $items = new LengthAwarePaginator(
            array_map(fn ($e) => array_diff_key($e, ['stack' => 1]) + ['has_stack' => $e['stack'] !== ''], array_slice($entries, ($page - 1) * $perPage, $perPage)),
            count($entries), $perPage, $page, ['pageName' => $this->pageName()]
        );

        return view('log::log_app_table', [
            'items' => $items,
            'levels' => array_keys(LogParser::LEVEL_CLASSES),
            // file-wide summary, hidden while a filter narrows the table
            'summary' => ($this->logFile && $this->search === '' && $this->level === '') ? $this->parser()->summary($this->logFile) : [],
        ])->layout('log::admin');
    }
}
