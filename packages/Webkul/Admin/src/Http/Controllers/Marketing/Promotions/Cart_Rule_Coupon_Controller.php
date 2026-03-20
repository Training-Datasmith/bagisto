<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Marketing\Promotions;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Marketing\Promotions\Cart_Rule_Coupon_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Mass_Destroy_Request;
use Webkul\Cart_Rule\Repositories\Cart_Rule_Coupon_Repository;
class Cart_Rule_Coupon_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Cart_Rule_Coupon_Repository $cart_rule_coupon_repository)
    {
    }
    /**
     * Index.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(int $id)
    {
        return datagrid(Cart_Rule_Coupon_Data_Grid::class)->process();
    }
    /**
     * Generate coupon code for cart rule.
     *
     * @param  int  $id
     */
    public function store($id): Json_Response
    {
        $this->validate(request(), ['coupon_qty' => 'required|integer|min:1', 'code_length' => 'required|integer|min:10', 'code_format' => 'required']);
        if (!$id) {
            return new Json_Response(['message' => trans('admin::app.promotions.cart-rules-coupons.cart-rule-not-defined-error')], 400);
        }
        $this->cart_rule_coupon_repository->generate_coupons(request()->only('coupon_qty', 'code_length', 'code_format', 'code_prefix', 'code_suffix'), $id);
        return new Json_Response(['message' => trans('admin::app.marketing.promotions.cart-rules-coupons.success', ['name' => 'Cart rule coupons'])]);
    }
    /**
     * Delete Generated coupon code
     */
    public function destroy(int $id): Json_Response
    {
        try {
            $this->cart_rule_coupon_repository->delete($id);
            return new Json_Response(['message' => trans('admin::app.marketing.promotions.cart-rules-coupons.delete-success')]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => trans('admin::app.marketing.promotions.cart-rules-coupons.cart-rule-not-defined-error')], 400);
        }
    }
    /**
     * Mass delete the coupons.
     */
    public function mass_destroy(Mass_Destroy_Request $mass_destroy_request): Json_Response
    {
        $coupon_ids = $mass_destroy_request->input('indices');
        foreach ($coupon_ids as $coupon_id) {
            $coupon = $this->cart_rule_coupon_repository->find($coupon_id);
            if ($coupon) {
                Event::dispatch('cart_rules.coupons.delete.before', $coupon);
                $this->cart_rule_coupon_repository->delete($coupon_id);
                Event::dispatch('cart_rules.coupons.delete.after', $coupon);
            }
        }
        return new Json_Response(['message' => trans('admin::app.marketing.promotions.cart-rules-coupons.mass-delete-success')]);
    }
}