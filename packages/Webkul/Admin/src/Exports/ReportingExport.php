<?php

declare (strict_types=1);
namespace Webkul\Admin\Exports;

use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\From_Collection;
class Reporting_Export implements From_Collection
{
    /**
     * Create a new instance.
     *
     * @param mixed records
     * @return void
     */
    public function __construct(protected $records = [])
    {
    }
    /**
     * function to create a blade view for export.
     *
     * @return Collection
     */
    public function collection()
    {
        $rows[] = Arr::pluck($this->records['columns'], 'label');
        foreach ($this->records['records'] as $key => $record) {
            $data = [];
            foreach ($this->records['columns'] as $column) {
                $data[$column['label']] = $record[$column['key']];
            }
            $rows[] = (object) $data;
        }
        return collect($rows);
    }
}