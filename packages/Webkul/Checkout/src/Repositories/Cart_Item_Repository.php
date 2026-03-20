<?php

declare (strict_types=1);
namespace Webkul\Checkout\Repositories;

use Webkul\Core\Eloquent\Repository;
class Cart_Item_Repository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Checkout\Contracts\CartItem';
    }
    /**
     * @param  int  $cartItemId
     * @return int
     */
    public function get_product($cart_item_id)
    {
        return $this->model->find($cart_item_id)->product->id;
    }
}