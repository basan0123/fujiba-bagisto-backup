<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => '凭证无效。',
                'not-activated'       => '您的账户尚未激活。',
                'verify-first'        => '请先验证您的电子邮件。',
                'success'             => '您已成功登录。',
            ],

            'logout' => [
                'no-login-agent' => '没有代理已登录。',
                'success'        => '您已成功登出。',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => '您输入的密码不正确。',
                    'success'          => '您的账户已成功更新。',
                ],
            ],
        ],

        'customers' => [
            'create-success' => '客户创建成功！',
            'update-success' => '客户更新成功！',
            'delete-success' => '客户删除成功！',
            'delete-failed'  => '客户删除失败！',
            'pending-orders' => '客户有待处理的订单！',
        ],

        'cart' => [
            'already-applied'     => '优惠券已使用！',
            'coupon-applied'      => '优惠券使用成功！',
            'coupon-removed'      => '优惠券删除成功！',
            'create-success'      => '购物车创建成功！',
            'invalid-coupon'      => '无效的优惠券代码！',
            'item-add-success'    => '商品已成功添加到购物车！',
            'item-remove-success' => '商品已成功从购物车中移除！',
            'item-update-success' => '商品更新成功！',
            'not-found'           => '购物车未找到！',
        ],

        'payment' => [
            'title' => 'POS 付款',

            'options' => [
                'cash' => [
                    'title'       => 'POS 现金付款',
                    'description' => '这是POS现金付款。',
                ],

                'card' => [
                    'title'       => 'POS 卡片付款',
                    'description' => '这是POS卡片付款。',
                ],

                'split' => [
                    'title'       => 'POS 分期付款',
                    'description' => '这是POS分期付款。',
                ],
            ],

            'no-items' => '购物车中没有商品以继续付款。',
            'success'  => '付款成功完成！',
        ],

        'shipping' => [
            'title'       => 'POS 运送',
            'description' => '这是免费的POS运送。',
        ],

        'order' => [
            'sync-success' => '订单同步成功！',
        ],

        'return' => [
            'create-success'              => '退款创建成功',
            'creation-error'              => '不允许创建退款。',
            'invalid-qty'                 => '发现无效的商品数量用于开票。',
            'invalid-refund-amount-error' => '退款金额必须大于零。',
            'refund-limit-error'          => '退款金额 :amount 无法处理。',
        ],

        'drawer' => [
            'create-success' => '抽屉成功打开！',
            'not-opened'     => '抽屉未打开。',
            'close-success'  => '抽屉成功关闭！',
        ],

        'products' => [
            'request-success' => '产品请求已成功提交。',
            'create-success'  => '产品创建成功！',
        ],

        'reports' => [
            'orders'                  => '订单',
            'average-order-value'     => '平均订单价值',
            'average-items-per-order' => '每订单平均商品数',
            'discounted-offers'       => '折扣优惠',
            'cash-payments'           => '现金支付',
            'other-payments'          => '其他支付',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => '订单备注',
                    'note-not-available' => '备注不可用！',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'Bagisto 销售点 (POS) 扩展。',
                    'title' => '销售点',

                    'settings' => [
                        'info'  => '启用 POS，设置常规配置、POS 产品和账单收据。',
                        'title' => '设置',

                        'general' => [
                            'footer-content'       => '页脚内容',
                            'footer-note'          => '页脚备注',
                            'frontend-url'         => '前端 URL',
                            'heading-on-login'     => '登录时标题',
                            'info'                 => '常规设置允许对 POS 用户页面进行配置，包括添加徽标、标题、页脚内容、页脚备注等。',
                            'pos-logo'             => 'POS 徽标',
                            'status'               => '状态',
                            'sub-heading-on-login' => '登录时副标题',
                            'title'                => '常规',
                        ],

                        'barcode' => [
                            'height'             => '高度',
                            'hide-barcode'       => '隐藏条形码',
                            'info'               => '条形码设置允许配置条形码生成，包括条形码高度、条形码宽度、条形码类型等。',
                            'prefix'             => '前缀',
                            'print-product-name' => '打印产品名称',
                            'title'              => '条形码',
                            'width'              => '宽度',

                            'generate-with' => [
                                'title' => '生成条形码方式',

                                'options' => [
                                    'product-id' => '产品 ID',
                                    'sku'        => '产品 SKU',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => '允许自定义产品的SKU',
                            'info'      => '产品设置允许配置产品SKU。',
                            'title'     => '产品',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => '分配产品',
            'banks'            => '银行',
            'barcode-products' => '条形码产品',
            'create'           => '创建',
            'delete'           => '删除',
            'edit'             => '编辑',
            'generate-barcode' => '生成条形码',
            'orders'           => '订单',
            'outlets'          => '门店',
            'pos'              => '销售点 (POS)',
            'preview'          => '预览',
            'print-barcode'    => '打印条形码',
            'receipts'         => '收据',
            'requests'         => '请求',
            'sales-report'     => '销售报告',
            'users'            => '代理',
            'view'             => '查看',
        ],

        'layouts' => [
            'banks'            => '银行',
            'barcode-products' => '条形码产品',
            'orders'           => '订单',
            'pos'              => '销售点 (POS)',
            'receipts'         => '收据',
            'requests'         => '请求',
            'sales-report'     => '销售报告',

            'users' => [
                'agents'   => '代理',
                'outlets'  => '门店',
                'title'    => '代理',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => '创建代理',
                    'pos-front'  => 'POS 前端',
                    'title'      => '代理',

                    'datagrid' => [
                        'action'              => '操作',
                        'delete'              => '删除',
                        'edit'                => '编辑',
                        'email'               => '电子邮件',
                        'full-name'           => '全名',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => '选定的代理已成功删除!',
                        'mass-update-success' => '选定的代理已成功更新!',
                        'outlet-name'         => '门店名称',
                        'profile-image'       => '头像',
                        'update-status'       => '更新状态',
                        'username'            => '用户名',

                        'status' => [
                            'title' => '状态',

                            'options' => [
                                'active'  => '激活',
                                'disable' => '禁用',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => '返回',
                    'confirm-password'  => '确认密码',
                    'email'             => '电子邮件',
                    'first-name'        => '名',
                    'general'           => '常规',
                    'image'             => '头像',
                    'last-name'         => '姓',
                    'outlet'            => '门店',
                    'outlet-and-status' => '门店 & 状态',
                    'password'          => '密码',
                    'save-btn'          => '保存代理',
                    'select-outlet'     => '选择门店',
                    'status'            => '状态',
                    'title'             => '添加代理',
                    'user-image'        => '上传代理头像',
                    'username'          => '用户名',
                ],

                'edit' => [
                    'back-btn'          => '返回',
                    'confirm-password'  => '确认密码',
                    'email'             => '电子邮件',
                    'first-name'        => '名',
                    'general'           => '常规',
                    'image'             => '头像',
                    'last-name'         => '姓',
                    'outlet'            => '门店',
                    'outlet-and-status' => '门店 & 状态',
                    'password'          => '密码',
                    'save-btn'          => '保存代理',
                    'select-outlet'     => '选择门店',
                    'status'            => '状态',
                    'title'             => '编辑代理',
                    'user-image'        => '上传代理头像',
                    'username'          => '用户名',
                ],

                'create-success' => '代理创建成功！',
                'delete-failed'  => '代理删除失败！',
                'delete-success' => '代理删除成功！',
                'update-success' => '代理更新成功！',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => '创建门店',
                    'pos-front'  => 'POS 前端',
                    'title'      => '门店',

                    'datagrid' => [
                        'action'              => '操作',
                        'active'              => '激活',
                        'assign'              => '分配产品',
                        'delete'              => '删除',
                        'edit'                => '编辑',
                        'id'                  => 'ID',
                        'inactive'            => '未激活',
                        'inventory-source'    => '库存来源',
                        'mass-delete-success' => '选定的门店已成功删除！',
                        'mass-update-success' => '选定的门店已成功更新！',
                        'name'                => '名称',
                        'receipt-title'       => '收据标题',
                        'status'              => '状态',
                        'title'               => 'POS 门店列表',
                        'update-status'       => '更新状态',
                    ],
                ],

                'create' => [
                    'address'                 => '地址',
                    'back-btn'                => '返回',
                    'btn-title'               => '保存门店',
                    'city'                    => '城市',
                    'country'                 => '国家',
                    'customer-care-number'    => '客户服务号码',
                    'email'                   => '电子邮件',
                    'general'                 => '常规',
                    'gst-number'              => 'GST 号码',
                    'inventory'               => '库存',
                    'inventory-source'        => '库存来源',
                    'low-stock-qty'           => '低库存数量',
                    'name'                    => '门店名称',
                    'phone'                   => '电话',
                    'postcode'                => '邮政编码',
                    'receipt'                 => '收据',
                    'select-country'          => '选择国家',
                    'select-inventory-source' => '选择库存来源',
                    'select-receipt'          => '选择收据',
                    'state'                   => '州/省',
                    'status'                  => '状态',
                    'store-address'           => '门店地址',
                    'title'                   => '添加门店',
                    'website'                 => '网站',
                ],

                'edit' => [
                    'address'                 => '地址',
                    'back-btn'                => '返回',
                    'btn-title'               => '保存门店',
                    'city'                    => '城市',
                    'country'                 => '国家',
                    'customer-care-number'    => '客户服务号码',
                    'email'                   => '电子邮件',
                    'general'                 => '常规',
                    'gst-number'              => 'GST 号码',
                    'inventory'               => '库存',
                    'inventory-source'        => '库存来源',
                    'low-stock-qty'           => '低库存数量',
                    'name'                    => '门店名称',
                    'phone'                   => '电话',
                    'postcode'                => '邮政编码',
                    'receipt'                 => '收据',
                    'select-country'          => '选择国家',
                    'select-inventory-source' => '选择库存来源',
                    'select-receipt'          => '选择收据',
                    'state'                   => '州/省',
                    'status'                  => '状态',
                    'store-address'           => '门店地址',
                    'title'                   => '编辑门店',
                    'website'                 => '网站',
                ],

                'assign' => [
                    'back-btn' => '返回',
                    'title'    => '管理门店产品',

                    'datagrid' => [
                        'active'              => '激活',
                        'assign'              => '分配',
                        'disable'             => '禁用',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'image'               => '图片',
                        'mass-assign-success' => '产品分配已成功更新！',
                        'name'                => '名称',
                        'out-of-stock'        => '库存不足',
                        'pos-status'          => 'POS 状态',
                        'price'               => '价格',
                        'product-image'       => '产品图片',
                        'qty'                 => '数量',
                        'qty-value'           => ':qty 可用',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => '状态',
                        'type'                => '类型',
                        'unassign'            => '取消分配',
                        'update-assign'       => '更新分配',
                    ],
                ],

                'create-success' => '门店创建成功！',
                'delete-failed'  => '门店删除失败！',
                'delete-success' => '门店删除成功！',
                'update-success' => '门店更新成功！',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => '条形码产品',

                'datagrid' => [
                    'barcode'               => '条形码',
                    'generate-barcode'      => '生成条形码',
                    'print-barcode'         => '打印条形码',
                    'id'                    => 'ID',
                    'id-value'              => 'ID - :id',
                    'image'                 => '图片',
                    'mass-generate-success' => '选定的产品条形码生成成功！',
                    'name'                  => '名称',
                    'out-of-stock'          => '库存不足',
                    'price'                 => '价格',
                    'product-image'         => '产品图片',
                    'qty'                   => '数量',
                    'qty-value'             => ':qty 可用',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => '状态',

                        'options' => [
                            'active'  => '激活',
                            'disable' => '禁用',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => '返回',
                'btn-title' => '打印',
                'qty'       => '数量',
                'title'     => '打印条形码',
            ],

            'generate-failed'  => '条形码生成失败！',
            'generate-success' => '条形码生成成功！',
        ],

        'orders' => [
            'index' => [
                'title' => '订单',

                'datagrid' => [
                    'customer-name' => '客户姓名',
                    'grand-total'   => '总金额',
                    'order-date'    => '订单日期',
                    'order-id'      => '订单ID',
                    'order-ref-id'  => '订单参考ID',
                    'view'          => '查看',

                    'status' => [
                        'title' => '状态',

                        'options' => [
                            'canceled'        => '已取消',
                            'closed'          => '已关闭',
                            'completed'       => '已完成',
                            'fraud'           => '欺诈',
                            'pending'         => '待处理',
                            'pending-payment' => '待付款',
                            'processing'      => '处理中',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => '请求',

                'datagrid' => [
                    'id'                  => 'ID',
                    'product-image'       => '产品图片',
                    'mass-update-error'   => '请求更新失败！',
                    'mass-update-success' => '选定的请求已成功更新！',
                    'product-name'        => '产品名称',
                    'outlet-name'         => '门店名称',
                    'qty-value'           => '数量 - :qty',
                    'request-date'        => '请求日期',
                    'requested-qty'       => '请求数量',
                    'update-status'       => '更新状态',
                    'user-name'           => '用户名',

                    'status' => [
                        'title' => '状态',

                        'options' => [
                            'complete' => '完成',
                            'decline'  => '拒绝',
                            'pending'  => '待处理',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => '返回',
                'btn-title' => '保存',
                'title'     => '请求的产品详细信息 #:id',

                'user-info' => [
                    'email'            => '电子邮件',
                    'name'             => '姓名',
                    'outlet-address'   => '门店地址',
                    'outlet-inventory' => '门店库存来源',
                    'outlet-name'      => '门店名称',
                    'title'            => '用户信息',
                ],

                'request-info' => [
                    'comment'       => '评论',
                    'product-name'  => '产品名称',
                    'qty-value'     => '数量 - :qty',
                    'request-date'  => '请求日期',
                    'requested-qty' => '请求数量',
                    'title'         => '请求信息',

                    'status' => [
                        'title' => '状态',

                        'options' => [
                            'complete' => '完成',
                            'decline'  => '拒绝',
                            'pending'  => '待处理',
                        ],
                    ],
                ],
            ],

            'update-failed'  => '请求更新失败！',
            'update-success' => '请求更新成功！',
        ],

        'banks' => [
            'index' => [
                'btn-title' => '创建银行',
                'title'     => '银行',

                'datagrid' => [
                    'active'              => '活跃',
                    'address'             => '银行地址',
                    'agent-name'          => '代理人',
                    'delete'              => '删除',
                    'disable'             => '禁用',
                    'id'                  => 'ID',
                    'mass-delete-success' => '选定的银行已成功删除！',
                    'name'                => '银行名称',
                    'status'              => '状态',
                ],
            ],

            'create' => [
                'back-btn'  => '返回',
                'btn-title' => '保存银行',
                'title'     => '创建新银行',

                'general' => [
                    'address' => '地址',
                    'email'   => '电子邮件',
                    'name'    => '名称',
                    'phone'   => '电话',
                    'title'   => '基本信息',
                ],

                'agent-and-status' => [
                    'agent'        => '分配POS代理',
                    'bank-status'  => '银行状态',
                    'select-agent' => '选择代理',
                    'title'        => 'POS代理与银行状态',
                ],
            ],

            'edit' => [
                'back-btn'  => '返回',
                'btn-title' => '保存银行',
                'title'     => '编辑银行',

                'general' => [
                    'address' => '地址',
                    'email'   => '电子邮件',
                    'name'    => '名称',
                    'phone'   => '电话',
                    'title'   => '基本信息',
                ],

                'agent-and-status' => [
                    'agent'        => '分配POS代理',
                    'bank-status'  => '银行状态',
                    'select-agent' => '选择代理',
                    'title'        => 'POS代理与银行状态',
                ],
            ],

            'create-success' => '银行创建成功！',
            'delete-failed'  => '银行删除失败！',
            'delete-success' => '银行删除成功！',
            'update-success' => '银行更新成功！',
        ],

        'sales-reports' => [
            'index' => [
                'title' => '销售报告',

                'datagrid' => [
                    'bank-name'      => '银行名称',
                    'grand-total'    => '总计',
                    'order-date'     => '订单日期',
                    'order-id'       => '订单ID',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => '订单备注',
                    'outlet-name'    => '门店名称',
                    'payment-method' => '支付方式',
                    'view'           => '查看',

                    'status' => [
                        'title' => '状态',

                        'options' => [
                            'canceled'        => '已取消',
                            'closed'          => '已关闭',
                            'completed'       => '已完成',
                            'fraud'           => '欺诈',
                            'pending'         => '待处理',
                            'pending-payment' => '待付款',
                            'processing'      => '处理中',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => '创建收据',
                'title'      => '收据',

                'datagrid' => [
                    'delete'              => '删除',
                    'edit'                => '编辑',
                    'id'                  => 'ID',
                    'mass-delete-success' => '选定的收据已成功删除！',
                    'preview'             => '预览',
                    'title'               => '标题',

                    'status' => [
                        'title' => '状态',

                        'options' => [
                            'active'   => '激活',
                            'inactive' => '非激活',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => '返回',
                'btn-title' => '保存收据',
                'title'     => '创建新收据',

                'general' => [
                    'cashier-name-label'      => '收银员名称标签',
                    'change-amount-label'     => '找零金额标签',
                    'credit-amount-label'     => '信用金额标签',
                    'discount-amt-label'      => '折扣金额标签',
                    'display-cashier-name'    => '显示收银员名称',
                    'display-change-amount'   => '显示找零金额',
                    'display-credit-amount'   => '显示信用金额',
                    'display-customer-name'   => '显示客户名称',
                    'display-date'            => '显示日期',
                    'display-discount-amt'    => '显示折扣金额',
                    'display-order-id'        => '显示订单ID',
                    'display-outlet-address'  => '显示门店地址',
                    'display-outlet-name'     => '显示门店名称',
                    'display-sub-total'       => '显示小计',
                    'display-tax'             => '显示税额',
                    'grand-total-label'       => '总计标签',
                    'order-id-label'          => '订单ID标签',
                    'receipt-title'           => '收据标题',
                    'show-order-barcode'      => '显示订单条形码',
                    'show-print-confirmation' => '显示打印确认',
                    'status'                  => '状态',
                    'sub-total-label'         => '小计标签',
                    'tax-label'               => '税额标签',
                    'title'                   => '基本信息',
                ],

                'logo' => [
                    'display-logo' => '显示logo',
                    'logo-alt'     => 'Logo替代文本',
                    'logo-height'  => 'Logo高度 (以px为单位)',
                    'logo-width'   => 'Logo宽度 (以px为单位)',
                    'title'        => 'Logo',
                    'upload-logo'  => '上传Logo',
                ],

                'header' => [
                    'header-content' => '页眉内容',
                    'title'          => '页眉',
                ],

                'footer' => [
                    'footer-content' => '页脚内容',
                    'title'          => '页脚',
                ],
            ],

            'edit' => [
                'back-btn'  => '返回',
                'btn-title' => '保存收据',
                'title'     => '编辑收据',

                'general' => [
                    'cashier-name-label'      => '收银员名称标签',
                    'change-amount-label'     => '找零金额标签',
                    'credit-amount-label'     => '信用金额标签',
                    'discount-amt-label'      => '折扣金额标签',
                    'display-cashier-name'    => '显示收银员名称',
                    'display-change-amount'   => '显示找零金额',
                    'display-credit-amount'   => '显示信用金额',
                    'display-customer-name'   => '显示客户名称',
                    'display-date'            => '显示日期',
                    'display-discount-amt'    => '显示折扣金额',
                    'display-order-id'        => '显示订单ID',
                    'display-outlet-address'  => '显示门店地址',
                    'display-outlet-name'     => '显示门店名称',
                    'display-sub-total'       => '显示小计',
                    'display-tax'             => '显示税额',
                    'grand-total-label'       => '总计标签',
                    'order-id-label'          => '订单ID标签',
                    'receipt-title'           => '收据标题',
                    'show-order-barcode'      => '显示订单条形码',
                    'show-print-confirmation' => '显示打印确认',
                    'status'                  => '状态',
                    'sub-total-label'         => '小计标签',
                    'tax-label'               => '税额标签',
                    'title'                   => '基本信息',
                ],

                'logo' => [
                    'display-logo' => '显示logo',
                    'logo-alt'     => 'Logo替代文本',
                    'logo-height'  => 'Logo高度 (以px为单位)',
                    'logo-width'   => 'Logo宽度 (以px为单位)',
                    'title'        => 'Logo',
                    'upload-logo'  => '上传Logo',
                ],

                'header' => [
                    'header-content' => '页眉内容',
                    'title'          => '页眉',
                ],

                'footer' => [
                    'footer-content' => '页脚内容',
                    'title'          => '页脚',
                ],
            ],

            'preview' => [
                'amount'         => '金额',
                'cashier'        => '收银员',
                'change-amount'  => '找零',
                'customer'       => '客户',
                'customer-email' => '客户电子邮件',
                'customer-name'  => '客户姓名',
                'customer-phone' => '客户电话',
                'date'           => '日期',
                'discount'       => '折扣',
                'email'          => '电子邮件',
                'grand-total'    => '总计',
                'order-id'       => '订单ID',
                'phone'          => '电话',
                'price'          => '价格',
                'product'        => '产品',
                'qty'            => '数量',
                'sub-total'      => '小计',
                'tax'            => '税额',
                'title'          => '收据预览',
                'total-qty'      => '总数量',
            ],

            'create-success' => '收据创建成功！',
            'delete-failed'  => '收据删除失败！',
            'delete-success' => '收据删除成功！',
            'update-success' => '收据更新成功！',
        ],
    ],

    'emails' => [
        'dear'     => '亲爱的 :name',
        'greeting' => '问候！',

        'registration' => [
            'message' => '恭喜！您的账户已成功创建。请登录您的账户并开始使用 POS 系统。',
            'subject' => 'POS 用户注册邮件',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => '登录',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
