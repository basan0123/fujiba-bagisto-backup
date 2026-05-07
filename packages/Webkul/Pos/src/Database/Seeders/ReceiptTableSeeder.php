<?php

namespace Webkul\Pos\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReceiptTableSeeder extends Seeder
{
    public function run()
    {
        $now = now()->toDateTimeString();

        DB::table('pos_receipts')->delete();

        DB::table('pos_receipts')->insert([
            [
                'title'                  => 'Preview Receipt',
                'status'                 => 1,
                'display_outlet_name'    => 1,
                'display_date'           => 1,
                'display_order_id'       => 1,
                'order_id_label'         => 'Order Id',
                'display_customer_name'  => 1,
                'display_sub_total'      => 1,
                'sub_total_label'        => 'Sub Total',
                'display_discount'       => 1,
                'discount_label'         => 'Discount',
                'display_tax'            => 1,
                'tax_label'              => 'Tax',
                'display_credit_amount'  => 1,
                'credit_amount_label'    => 'Credit Amount',
                'display_change_amount'  => 1,
                'credit_change_label'    => 'Change',
                'display_cashier_name'   => 1,
                'cashier_label'          => 'Cashier',
                'display_outlet_address' => 1,
                'grand_total_label'      => 'Grand Total',
                'display_logo'           => 1,
                'logo'                   => 'pos-receipts/1/logo.png',
                'logo_width'             => '50px',
                'logo_height'            => '50px',
                'logo_alt'               => 'logo',
                'header_content'         => 'Header',
                'footer_content'         => 'Footer',
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
        ]);
    }
}
