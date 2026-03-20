<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Marketing\Promotions;

use Exception;
use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Validation_Exception;
use Webkul\Admin\Data_Grids\Marketing\Promotions\Cart_Rule_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Cart_Rule_Request;
use Webkul\Cart_Rule\Repositories\Cart_Rule_Repository;
class Cart_Rule_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Cart_Rule_Repository $cart_rule_repository)
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
            return datagrid(Cart_Rule_Data_Grid::class)->process();
        }
        return view('admin::marketing.promotions.cart-rules.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin::marketing.promotions.cart-rules.create');
    }
    /**
     * Copy a given Cart Rule id. Always make the copy is inactive so the
     * user is able to configure it before setting it live.
     *
     * @return \Illuminate\View\View
     */
    public function copy(int $cart_rule_id)
    {
        $cart_rule = $this->cart_rule_repository->with(['channels', 'customer_groups'])->find_or_fail($cart_rule_id);
        $copied_cart_rule = $cart_rule->replicate()->fill(['status' => 0, 'name' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.copy-of', ['value' => $cart_rule->name])]);
        $copied_cart_rule->save();
        foreach ($copied_cart_rule->channels as $channel) {
            $copied_cart_rule->channels()->save($channel);
        }
        foreach ($copied_cart_rule->customer_groups as $group) {
            $copied_cart_rule->customer_groups()->save($group);
        }
        return view('admin::marketing.promotions.cart-rules.edit', ['cartRule' => $copied_cart_rule]);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Cart_Rule_Request $cart_rule_request)
    {
        try {
            Event::dispatch('promotions.cart_rule.create.before');
            $cart_rule = $this->cart_rule_repository->create($cart_rule_request->all());
            Event::dispatch('promotions.cart_rule.create.after', $cart_rule);
            session()->flash('success', trans('admin::app.marketing.promotions.cart-rules.create.create-success'));
            return redirect()->route('admin.marketing.promotions.cart_rules.index');
        } catch (Validation_Exception $e) {
            if ($first_error = collect($e->errors())->first()) {
                session()->flash('error', $first_error[0]);
            }
        }
        return redirect()->back();
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $cart_rule = $this->cart_rule_repository->find_or_fail($id);
        return view('admin::marketing.promotions.cart-rules.edit', compact('cartRule'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Cart_Rule_Request $cart_rule_request, int $id)
    {
        try {
            $cart_rule = $this->cart_rule_repository->find_or_fail($id);
            if ($cart_rule->coupon_type) {
                if ($cart_rule->cart_rule_coupon) {
                    $this->validate(request(), ['coupon_code' => 'required_if:use_auto_generation,==,0|unique:cart_rule_coupons,code,' . $cart_rule->cart_rule_coupon->id]);
                } else {
                    $this->validate(request(), ['coupon_code' => 'required_if:use_auto_generation,==,0|unique:cart_rule_coupons,code']);
                }
            }
            Event::dispatch('promotions.cart_rule.update.before', $id);
            $cart_rule = $this->cart_rule_repository->update($cart_rule_request->all(), $id);
            Event::dispatch('promotions.cart_rule.update.after', $cart_rule);
            session()->flash('success', trans('admin::app.marketing.promotions.cart-rules.edit.update-success'));
            return redirect()->route('admin.marketing.promotions.cart_rules.index');
        } catch (Validation_Exception $e) {
            if ($first_error = collect($e->errors())->first()) {
                session()->flash('error', $first_error[0]);
            }
        }
        return redirect()->back();
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        $this->cart_rule_repository->find_or_fail($id);
        try {
            Event::dispatch('promotions.cart_rule.delete.before', $id);
            $this->cart_rule_repository->delete($id);
            Event::dispatch('promotions.cart_rule.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.marketing.promotions.cart-rules.delete-success')]);
        } catch (Exception $e) {
        }
        return new Json_Response(['message' => trans('admin::app.marketing.promotions.cart-rules.delete-failed')], 400);
    }
}