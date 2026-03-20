<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Settings\Data_Transfer;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Data_Grids\Settings\Data_Transfer\Import_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Data_Transfer\Helpers\Import;
use Webkul\Data_Transfer\Repositories\Import_Repository;
class Import_Controller extends Controller
{
    /**
     * Supported formats.
     */
    protected array $supported_formats = ['csv', 'xls', 'xlsx', 'xml'];
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Import_Repository $import_repository, protected Import $import_helper)
    {
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(Import_Data_Grid::class)->process();
        }
        return view('admin::settings.data-transfer.imports.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin::settings.data-transfer.imports.create', ['supportedFormats' => $this->supported_formats]);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $importers = implode(',', array_keys(config('importers')));
        $supported_formats = implode(',', $this->supported_formats);
        $this->validate(request(), ['type' => 'required|in:' . $importers, 'action' => 'required|in:append,delete', 'validation_strategy' => 'required|in:stop-on-errors,skip-errors', 'allowed_errors' => 'required|integer|min:0', 'field_separator' => 'required', 'file' => 'required|file|extensions:' . $supported_formats . '|mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/xml,application/xml']);
        Event::dispatch('data_transfer.imports.create.before');
        $data = request()->only(['type', 'action', 'process_in_queue', 'validation_strategy', 'validation_strategy', 'allowed_errors', 'field_separator', 'images_directory_path']);
        if (!isset($data['process_in_queue'])) {
            $data['process_in_queue'] = false;
        } else {
            $data['process_in_queue'] = true;
        }
        $file = request()->file('file');
        $safe_filename = uniqid() . '_' . hash('sha256', $file->get_client_original_name());
        $extension = strtolower($file->get_client_original_extension());
        $import = $this->import_repository->create(array_merge(['file_path' => request()->file('file')->store_as('imports', $safe_filename . '.' . $extension, 'private')], $data));
        Event::dispatch('data_transfer.imports.create.after', $import);
        session()->flash('success', trans('admin::app.settings.data-transfer.imports.create-success'));
        return redirect()->route('admin.settings.data_transfer.imports.import', $import->id);
    }
    /**
     * Show the form for editing a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        return view('admin::settings.data-transfer.imports.edit', ['import' => $this->import_repository->find_or_fail($id), 'supportedFormats' => $this->supported_formats]);
    }
    /**
     * Update a resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $importers = implode(',', array_keys(config('importers')));
        $supported_formats = implode(',', $this->supported_formats);
        $import = $this->import_repository->find_or_fail($id);
        $this->validate(request(), ['type' => 'required|in:' . $importers, 'action' => 'required|in:append,delete', 'validation_strategy' => 'required|in:stop-on-errors,skip-errors', 'allowed_errors' => 'required|integer|min:0', 'field_separator' => 'required', 'file' => 'nullable|file|extensions:' . $supported_formats . '|mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/xml,application/xml']);
        Event::dispatch('data_transfer.imports.update.before');
        $data = array_merge(request()->only(['type', 'action', 'process_in_queue', 'validation_strategy', 'validation_strategy', 'allowed_errors', 'field_separator', 'images_directory_path']), ['state' => 'pending', 'processed_rows_count' => 0, 'invalid_rows_count' => 0, 'errors_count' => 0, 'errors' => null, 'error_file_path' => null, 'started_at' => null, 'completed_at' => null, 'summary' => null]);
        Storage::disk('private')->delete($import->error_file_path ?? '');
        $file = request()->file('file');
        if ($file && $file->is_valid()) {
            $safe_filename = uniqid() . '_' . hash('sha256', $file->get_client_original_name());
            $extension = strtolower($file->get_client_original_extension());
            Storage::disk('private')->delete($import->file_path);
            $data['file_path'] = $file->store_as('imports', $safe_filename . '.' . $extension, 'private');
        }
        if (!isset($data['process_in_queue'])) {
            $data['process_in_queue'] = false;
        }
        $import = $this->import_repository->update($data, $import->id);
        Event::dispatch('data_transfer.imports.update.after', $import);
        session()->flash('success', trans('admin::app.settings.data-transfer.imports.update-success'));
        return redirect()->route('admin.settings.data_transfer.imports.import', $import->id);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $import = $this->import_repository->find_or_fail($id);
        try {
            Storage::disk('private')->delete($import->file_path);
            Storage::disk('private')->delete($import->error_file_path ?? '');
            $this->import_repository->delete($id);
            return new Json_Response(['message' => trans('admin::app.settings.data-transfer.imports.delete-success')]);
        } catch (\Exception $e) {
        }
        return response()->json(['message' => trans('admin::app.settings.data-transfer.imports.delete-failed')], 500);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function import(int $id)
    {
        $import = $this->import_repository->find_or_fail($id);
        $is_valid = $this->import_helper->set_import($import)->is_valid();
        if ($import->state == Import::STATE_LINKING) {
            if ($this->import_helper->is_indexing_required()) {
                $state = Import::STATE_INDEXING;
            } else {
                $state = Import::STATE_COMPLETED;
            }
        } elseif ($import->state == Import::STATE_INDEXING) {
            $state = Import::STATE_COMPLETED;
        } else {
            $state = Import::STATE_COMPLETED;
        }
        $stats = $this->import_helper->stats($state);
        $import->unset_relations();
        return view('admin::settings.data-transfer.imports.import', compact('import', 'isValid', 'stats'));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function validate_import(int $id): Json_Response
    {
        $import = $this->import_repository->find_or_fail($id);
        $is_valid = $this->import_helper->set_import($import)->validate();
        return new Json_Response(['is_valid' => $is_valid, 'import' => $this->import_helper->get_import()->unset_relations()]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function start(int $id): Json_Response
    {
        $import = $this->import_repository->find_or_fail($id);
        if (!$import->processed_rows_count) {
            return new Json_Response(['message' => trans('admin::app.settings.data-transfer.imports.nothing-to-import')], 400);
        }
        $this->import_helper->set_import($import);
        if (!$this->import_helper->is_valid()) {
            return new Json_Response(['message' => trans('admin::app.settings.data-transfer.imports.not-valid')], 400);
        }
        if ($import->process_in_queue && config('queue.default') == 'sync') {
            return new Json_Response(['message' => trans('admin::app.settings.data-transfer.imports.setup-queue-error')], 400);
        }
        /**
         * Set the import state to processing.
         */
        if ($import->state == Import::STATE_VALIDATED) {
            $this->import_helper->started();
        }
        /**
         * Get the first pending batch to import.
         */
        $import_batch = $import->batches->where('state', Import::STATE_PENDING)->first();
        if ($import_batch) {
            /**
             * Start the import process.
             */
            try {
                if ($import->process_in_queue) {
                    $this->import_helper->start();
                } else {
                    $this->import_helper->start($import_batch);
                }
            } catch (\Exception $e) {
                return new Json_Response(['message' => $e->get_message()], 400);
            }
        } else if ($this->import_helper->is_linking_required()) {
            $this->import_helper->linking();
        } elseif ($this->import_helper->is_indexing_required()) {
            $this->import_helper->indexing();
        } else {
            $this->import_helper->completed();
        }
        return new Json_Response(['stats' => $this->import_helper->stats(Import::STATE_PROCESSED), 'import' => $this->import_helper->get_import()->unset_relations()]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function link(int $id): Json_Response
    {
        $import = $this->import_repository->find_or_fail($id);
        if (!$import->processed_rows_count) {
            return new Json_Response(['message' => trans('admin::app.settings.data-transfer.imports.nothing-to-import')], 400);
        }
        $this->import_helper->set_import($import);
        if (!$this->import_helper->is_valid()) {
            return new Json_Response(['message' => trans('admin::app.settings.data-transfer.imports.not-valid')], 400);
        }
        /**
         * Set the import state to linking.
         */
        if ($import->state == Import::STATE_PROCESSED) {
            $this->import_helper->linking();
        }
        /**
         * Get the first processing batch to link.
         */
        $import_batch = $import->batches->where('state', Import::STATE_PROCESSED)->first();
        /**
         * Set the import state to linking/completed.
         */
        if ($import_batch) {
            /**
             * Start the resource linking process.
             */
            try {
                $this->import_helper->link($import_batch);
            } catch (\Exception $e) {
                return new Json_Response(['message' => $e->get_message()], 400);
            }
        } else if ($this->import_helper->is_indexing_required()) {
            $this->import_helper->indexing();
        } else {
            $this->import_helper->completed();
        }
        return new Json_Response(['stats' => $this->import_helper->stats(Import::STATE_LINKED), 'import' => $this->import_helper->get_import()->unset_relations()]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function index_data(int $id): Json_Response
    {
        $import = $this->import_repository->find_or_fail($id);
        if (!$import->processed_rows_count) {
            return new Json_Response(['message' => trans('admin::app.settings.data-transfer.imports.nothing-to-import')], 400);
        }
        $this->import_helper->set_import($import);
        if (!$this->import_helper->is_valid()) {
            return new Json_Response(['message' => trans('admin::app.settings.data-transfer.imports.not-valid')], 400);
        }
        /**
         * Set the import state to linking.
         */
        if ($import->state == Import::STATE_LINKED) {
            $this->import_helper->indexing();
        }
        /**
         * Get the first processing batch to link.
         */
        $import_batch = $import->batches->where('state', Import::STATE_LINKED)->first();
        /**
         * Set the import state to linking/completed.
         */
        if ($import_batch) {
            /**
             * Start the resource linking process.
             */
            try {
                $this->import_helper->index($import_batch);
            } catch (\Exception $e) {
                return new Json_Response(['message' => $e->get_message()], 400);
            }
        } else {
            /**
             * Set the import state to completed.
             */
            $this->import_helper->completed();
        }
        return new Json_Response(['stats' => $this->import_helper->stats(Import::STATE_INDEXED), 'import' => $this->import_helper->get_import()->unset_relations()]);
    }
    /**
     * Returns import stats.
     */
    public function stats(int $id, string $state = Import::STATE_PROCESSED): Json_Response
    {
        $import = $this->import_repository->find_or_fail($id);
        $stats = $this->import_helper->set_import($import)->stats($state);
        return new Json_Response(['stats' => $stats, 'import' => $this->import_helper->get_import()->unset_relations()]);
    }
    /**
     * Download sample file.
     */
    public function download_sample(string $type, string $format)
    {
        $sample_path = config("importers.{$type}.sample_paths.{$format}");
        return Storage::download($sample_path);
    }
    /**
     * Download import file.
     */
    public function download(int $id)
    {
        $import = $this->import_repository->find_or_fail($id);
        return Storage::disk('private')->download($import->file_path);
    }
    /**
     * Download import error report.
     */
    public function download_error_report(int $id)
    {
        $import = $this->import_repository->find_or_fail($id);
        if (!$import->error_file_path) {
            abort(404);
        }
        return Storage::disk('private')->download($import->error_file_path);
    }
}