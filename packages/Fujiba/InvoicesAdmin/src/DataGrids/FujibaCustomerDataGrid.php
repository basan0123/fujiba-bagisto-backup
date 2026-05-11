<?php

namespace Fujiba\InvoicesAdmin\DataGrids;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\DataGrids\Customers\CustomerDataGrid;

/**
 * Adds a profile avatar column and a "recent activity first" default sort to
 * /admin/customers. Recency is the greatest of the customer's latest cart
 * touch and their signup time, so newly-registered users and active shoppers
 * float to the top while dormant accounts sink. The user's clicked-column
 * sort still wins via the parent's orderBy(), this only sets the default.
 */
class FujibaCustomerDataGrid extends CustomerDataGrid
{
    public function prepareQueryBuilder()
    {
        $qb = parent::prepareQueryBuilder();

        $tablePrefix = DB::getTablePrefix();

        $qb->leftJoinSub(
            DB::table('cart')
                ->select('customer_id', DB::raw('MAX(updated_at) as last_cart_at'))
                ->whereNotNull('customer_id')
                ->groupBy('customer_id'),
            'cart_recent',
            'cart_recent.customer_id',
            '=',
            'customers.id'
        );

        $qb->addSelect(
            'customers.image as image',
            'customers.created_at as customer_created_at',
            'cart_recent.last_cart_at as last_cart_at'
        );

        $qb->orderByRaw(
            'GREATEST(COALESCE('.$tablePrefix.'cart_recent.last_cart_at, '.$tablePrefix.'customers.created_at), '.$tablePrefix.'customers.created_at) DESC'
        );

        return $qb;
    }

    public function prepareColumns()
    {
        parent::prepareColumns();

        $this->addColumn([
            'index'      => 'image_url',
            'label'      => trans('admin::app.customers.customers.index.datagrid.name'),
            'type'       => 'string',
            'sortable'   => false,
            'filterable' => false,
            'visibility' => false,
            'closure'    => function ($row) {
                if (! $row->image) {
                    return null;
                }

                // Customers imported from ChatPion store an absolute Messenger
                // CDN URL in `image`; native uploads store a relative storage
                // path. Pass through absolute URLs verbatim.
                return preg_match('#^https?://#', $row->image)
                    ? $row->image
                    : Storage::url($row->image);
            },
        ]);

        $this->addColumn([
            'index'      => 'last_cart_at',
            'label'      => 'Last cart activity',
            'type'       => 'datetime',
            'sortable'   => true,
            'filterable' => false,
            'visibility' => false,
        ]);
    }
}
