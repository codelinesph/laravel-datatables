<?php
namespace Codelines\LaravelDatatables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;

class LaravelDatatables
{
    protected string $className;
    protected Request $request;
    protected Builder $query;

    /**
     * LaravelDatatables constructor.
     * @param $className
     * @param Request $request
     * @param Builder $query
     */
    public function __construct(string $className, Request $request, Builder $query)
    {
        $this->className = $className;
        $this->request = $request;
        $this->query = $query;
    }


    public static function dtPaginate(Builder $query, $className, $request, int $perPage = 10): array
    {
        $query2 = $query;

        $ordering = $request->input('order');
        $pagesAll = $request->input('page') === 'all';

        if($request->has('length')){
            $perPage = $request->input('length');
        }
g
        $orders = [];

        $query = (new self($className, $request, $query))->parse();

        if ($ordering) {
            foreach ($ordering as $order) {
                $col = $request->input('columns.' . $order['column'] . '.name');
                if ($col) {
                    $orders[] = [$col, $order['dir']];
                }
            }
        }

        if ($orders) {
            foreach ($orders as $order) {
                if (str_contains($order[0], '.')) {
                    $query->orderBy($order[0], $order['1']);
                } else {
                    $query->orderBy($order[0], $order['1']);
                }
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        $total = $query2->count();
        if(!$pagesAll){
            $query->offset((int) $request->input('start'))->limit($perPage);
        }
        $paginated = $query->get();
        $records['data'] = $paginated->toArray();
        $records['recordsTotal'] = $total;
        $records['recordsFiltered'] = $total;
        $records['draw'] = (int) Request::input('draw');

        return $records;
    }

    public function parse(): Builder
    {
        $this->parseSearchable()->parseFilters();
        return $this->query;
    }

    protected function parseSearchable(): self
    {
        if (
            $this->className &&
            $this->request &&
            defined($this->className . '::SEARCHABLES') &&
            $this->request->has('search.value')
        ) {
            $searchKey = $this->request->input('search.value');

            $this->query->where(function ($query) use ($searchKey) {
                foreach (($this->className)::SEARCHABLES as $searchable){
                    $query->orWhere($searchable, 'like', '%' . $searchKey . '%');
                }
            });
        }

        return $this;
    }

    protected function parseFilters(): self
    {
        if (
            $this->className &&
            $this->request &&
            defined($this->className . '::FILTERS')
        ) {
            $this->query->where(function ($query) {
                foreach (($this->className)::FILTERS as $filter) {
                    if($this->request->has($filter)){
                        $key = $this->request->input($filter);
                        $query->orWhere($filter, '=', $key);
                    }
                }
            });
        }

        return $this;
    }
}
