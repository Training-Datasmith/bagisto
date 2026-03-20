<?php

declare (strict_types=1);
namespace Webkul\Admin\Helpers\Reporting;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Repositories\Visit_Repository;
class Visitor extends Abstract_Reporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(protected Visit_Repository $visit_repository)
    {
        parent::__construct();
    }
    /**
     * Retrieves total visitors and their progress.
     *
     * @param  string  $visitableType
     */
    public function get_total_visitors_progress($visitable_type = null): array
    {
        return ['previous' => $previous = $this->get_total_visitors($this->last_start_date, $this->last_end_date, $visitable_type), 'current' => $current = $this->get_total_visitors($this->start_date, $this->end_date, $visitable_type), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves total visitors and their progress.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $visitableType
     * @return array
     */
    public function get_total_visitors($start_date, $end_date, $visitable_type = null): int
    {
        if ($visitable_type) {
            return $this->visit_repository->reset_model()->where('visitable_type', $visitable_type)->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->get()->count();
        }
        return $this->visit_repository->reset_model()->where_null('visitable_id')->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->get()->count();
    }
    /**
     * Retrieves unique visitors and their progress.
     *
     * @param  string  $visitableType
     */
    public function get_total_unique_visitors_progress($visitable_type = null): array
    {
        return ['previous' => $previous = $this->get_total_unique_visitors($this->last_start_date, $this->last_end_date, $visitable_type), 'current' => $current = $this->get_total_unique_visitors($this->start_date, $this->end_date, $visitable_type), 'progress' => $this->get_percentage_change($previous, $current)];
    }
    /**
     * Retrieves total unique visitors
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $visitableType
     * @return array
     */
    public function get_total_unique_visitors($start_date, $end_date, $visitable_type = null): int
    {
        if ($visitable_type) {
            return $this->visit_repository->reset_model()->where('visitable_type', $visitable_type)->group_by(DB::raw('CONCAT(ip, "-", visitor_id, "-", visitable_type)'))->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->get()->count();
        }
        return $this->visit_repository->reset_model()->where_null('visitable_id')->group_by(DB::raw('CONCAT(ip, "-", visitor_id)'))->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->get()->count();
    }
    /**
     * Returns previous sales over time
     *
     * @param  string  $visitableType
     */
    public function get_previous_total_visitors_over_time($visitable_type = null): array
    {
        return $this->get_total_visitors_over_time($this->last_start_date, $this->last_end_date, 'auto', $visitable_type);
    }
    /**
     * Returns current sales over time
     *
     * @param  string  $visitableType
     */
    public function get_current_total_visitors_over_time($visitable_type = null): array
    {
        return $this->get_total_visitors_over_time($this->start_date, $this->end_date, 'auto', $visitable_type);
    }
    /**
     * Returns previous sales over week
     *
     * @param  string  $visitableType
     */
    public function get_previous_total_visitors_over_week($visitable_type = null): array
    {
        return $this->get_total_visitors_over_week($this->last_start_date, $this->last_end_date, $visitable_type);
    }
    /**
     * Returns current sales over week
     *
     * @param  string  $visitableType
     */
    public function get_current_total_visitors_over_week($visitable_type = null): array
    {
        return $this->get_total_visitors_over_week($this->start_date, $this->end_date, $visitable_type);
    }
    /**
     * Gets visitable with most visits.
     *
     * @param  string  $visitableType
     * @param  int  $limit
     */
    public function get_visitable_with_most_visits($visitable_type = null, $limit = null): Collection
    {
        $visits = $this->visit_repository->reset_model()->add_select('id', 'visitable_type', 'visitable_id', DB::raw('COUNT(*) as visits'))->where('visitable_type', $visitable_type)->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$this->start_date, $this->end_date])->group_by('visitable_id')->order_by_desc('visits')->limit($limit)->get();
        $visits->map(function ($visit) {
            $visit->name = $visit->visitable->name;
        });
        return $visits;
    }
    /**
     * Generates visitor graph data.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @param  string  $visitableType
     */
    public function get_total_visitors_over_time($start_date, $end_date, $period = 'auto', $visitable_type = null): array
    {
        $config = $this->get_time_interval($start_date, $end_date, $period);
        $group_column = $config['group_column'];
        $results = $this->visit_repository->reset_model()->select(DB::raw("{$group_column} AS date"), DB::raw('COUNT(*) AS total'))->where_null('visitable_id')->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->group_by('date')->get();
        $stats = [];
        foreach ($config['intervals'] as $interval) {
            $total = $results->where('date', $interval['filter'])->first();
            $stats[] = ['label' => $interval['start'], 'total' => $total?->total ?? 0];
        }
        return $stats;
    }
    /**
     * Generates visitor over week graph data.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $visitableType
     */
    public function get_total_visitors_over_week($start_date, $end_date, $visitable_type = null): array
    {
        $stats = [];
        $week_days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $visits = $this->visit_repository->reset_model()->select(DB::raw('DAYNAME(created_at) AS day'), DB::raw('COUNT(*) AS count'))->where_null('visitable_id')->where_in('channel_id', $this->channel_ids)->where_between('created_at', [$start_date, $end_date])->group_by('day')->get();
        foreach ($week_days as $day) {
            $total = $visits->where('day', $day)->first();
            $stats['label'][] = $day;
            $stats['total'][] = $total?->count ?? 0;
        }
        return $stats;
    }
}