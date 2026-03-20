<?php

declare (strict_types=1);
namespace Webkul\Admin\Helpers\Reporting;

use Carbon\Carbon_Period;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
abstract class Abstract_Reporting
{
    /**
     * The channel ids.
     */
    protected array $channel_ids;
    /**
     * The starting date for a given period.
     */
    protected Carbon $start_date;
    /**
     * The ending date for a given period.
     */
    protected Carbon $end_date;
    /**
     * The starting date for the previous period.
     */
    protected Carbon $last_start_date;
    /**
     * The ending date for the previous period.
     */
    protected Carbon $last_end_date;
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->set_channel(request()->query('channel'));
        $this->set_start_date(request()->date('start'));
        $this->set_end_date(request()->date('end'));
    }
    /**
     * Sets the channel IDs and codes.
     */
    public function set_channel(?string $code = null): self
    {
        $this->channel_ids = core()->get_all_channels()->filter(function ($channel) use ($code) {
            return $code ? $channel->code == $code : true;
        })->pluck('id')->to_array();
        // $this->channelIds = [2];
        return $this;
    }
    /**
     * Set the start date or default to 30 days ago if not provided.
     *
     * @param  \Carbon\Carbon|null  $startDate
     * @return void
     */
    public function set_start_date(?Carbon $start_date = null): self
    {
        $this->start_date = $start_date ? $start_date->start_of_day() : now()->sub_days(30)->start_of_day();
        $this->set_last_start_date();
        return $this;
    }
    /**
     * Sets the end date to the provided date's end of day, or to the current
     * date if not provided or if the provided date is in the future.
     *
     * @param  \Carbon\Carbon|null  $endDate
     * @return void
     */
    public function set_end_date(?Carbon $end_date = null): self
    {
        $this->end_date = $end_date && $end_date->end_of_day() <= now() ? $end_date->end_of_day() : now();
        $this->set_last_end_date();
        return $this;
    }
    /**
     * Get the start date.
     *
     * @return \Carbon\Carbon
     */
    public function get_start_date(): Carbon
    {
        return $this->start_date;
    }
    /**
     * Get the end date.
     *
     * @return \Carbon\Carbon
     */
    public function get_end_date(): Carbon
    {
        return $this->end_date;
    }
    /**
     * Sets the start date for the last period.
     */
    private function set_last_start_date(): void
    {
        if (!isset($this->start_date)) {
            $this->set_start_date(request()->date('start'));
        }
        if (!isset($this->end_date)) {
            $this->set_end_date(request()->date('end'));
        }
        $this->last_start_date = $this->start_date->clone()->sub_days($this->start_date->diff_in_days($this->end_date));
    }
    /**
     * Sets the end date for the last period.
     */
    private function set_last_end_date(): void
    {
        $this->last_end_date = $this->start_date->clone();
    }
    /**
     * Get the last start date.
     *
     * @return \Carbon\Carbon
     */
    public function get_last_start_date(): Carbon
    {
        return $this->last_start_date;
    }
    /**
     * Get the last end date.
     *
     * @return \Carbon\Carbon
     */
    public function get_last_end_date(): Carbon
    {
        return $this->last_end_date;
    }
    /**
     * Calculate the percentage change between previous and current values.
     *
     * @param  float|int  $previous
     * @param  float|int  $current
     */
    public function get_percentage_change($previous, $current): float|int
    {
        if (!$previous) {
            return $current ? 100 : 0;
        }
        return ($current - $previous) / $previous * 100;
    }
    /**
     * Returns time intervals.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @return array
     */
    public function get_time_interval($start_date, $end_date, $period)
    {
        if ($period == 'auto') {
            $total_months = $start_date->diff_in_months($end_date) + 1;
            /**
             * If the difference between the start and end date is more than 5 months
             */
            $intervals = $this->get_months_interval($start_date, $end_date);
            if (!empty($intervals)) {
                return ['group_column' => 'MONTH(created_at)', 'intervals' => $intervals];
            }
            /**
             * If the difference between the start and end date is more than 6 weeks
             */
            $intervals = $this->get_weeks_interval($start_date, $end_date);
            if (!empty($intervals)) {
                return ['group_column' => 'WEEK(created_at)', 'intervals' => $intervals];
            }
            /**
             * If the difference between the start and end date is less than 6 weeks
             */
            return ['group_column' => 'DAYOFYEAR(created_at)', 'intervals' => $this->get_days_interval($start_date, $end_date)];
        } else {
            $date_period = Carbon_Period::create($this->start_date, "1 {$period}", $this->end_date);
            if ($period == 'year') {
                $formatter = '?';
            } elseif ($period == 'month') {
                $formatter = '?-?';
            } else {
                $formatter = '?-?-?';
            }
            $group_column = 'DATE_FORMAT(created_at, "' . Str::replace_array('?', ['%Y', '%m', '%d'], $formatter) . '")';
            $intervals = [];
            foreach ($date_period as $date) {
                $formatted_date = $date->format(Str::replace_array('?', ['Y', 'm', 'd'], $formatter));
                $intervals[] = ['filter' => $formatted_date, 'start' => $formatted_date];
            }
            return ['group_column' => $group_column, 'intervals' => $intervals];
        }
    }
    /**
     * Returns time intervals.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function get_months_interval($start_date, $end_date)
    {
        $intervals = [];
        $total_months = $start_date->diff_in_months($end_date) + 1;
        /**
         * If the difference between the start and end date is less than 5 months
         */
        if ($total_months <= 5) {
            return $intervals;
        }
        for ($i = 0; $i < $total_months; $i++) {
            $interval_start_date = clone $start_date;
            $interval_start_date->add_months($i);
            $start = $interval_start_date->start_of_day();
            $end = $total_months - 1 == $i ? $end_date : $interval_start_date->add_month()->sub_day()->end_of_day();
            $intervals[] = ['filter' => $start->month, 'start' => $start->format('d M'), 'end' => $end->format('d M')];
        }
        return $intervals;
    }
    /**
     * Returns time intervals.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function get_weeks_interval($start_date, $end_date)
    {
        $intervals = [];
        $start_week_day = Carbon::create_from_time_string(core()->x_week_range($start_date, 0) . ' 00:00:01');
        $end_week_day = Carbon::create_from_time_string(core()->x_week_range($end_date, 1) . ' 23:59:59');
        $total_weeks = $start_week_day->diff_in_weeks($end_week_day);
        /**
         * If the difference between the start and end date is less than 6 weeks
         */
        if ($total_weeks <= 6) {
            return $intervals;
        }
        for ($i = 0; $i < $total_weeks; $i++) {
            $interval_start_date = clone $start_date;
            $interval_start_date->add_weeks($i);
            $start = $i == 0 ? $start_date : Carbon::create_from_time_string(core()->x_week_range($interval_start_date, 0) . ' 00:00:01');
            $end = $total_weeks - 1 == $i ? $end_date : Carbon::create_from_time_string(core()->x_week_range($interval_start_date->sub_day(), 1) . ' 23:59:59');
            $intervals[] = ['filter' => $start->week, 'start' => $start->format('d M'), 'end' => $end->format('d M')];
        }
        return $intervals;
    }
    /**
     * Returns time intervals.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function get_days_interval($start_date, $end_date)
    {
        $intervals = [];
        $total_days = $start_date->diff_in_days($end_date) + 1;
        for ($i = 0; $i < $total_days; $i++) {
            $interval_start_date = clone $start_date;
            $interval_start_date->add_days($i);
            $intervals[] = ['filter' => $interval_start_date->day_of_year, 'start' => $interval_start_date->start_of_day()->format('d M'), 'end' => $interval_start_date->end_of_day()->format('d M')];
        }
        return $intervals;
    }
}