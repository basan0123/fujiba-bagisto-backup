<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => '無効な認証情報です。',
                'not-activated'       => 'アカウントがアクティブではありません。',
                'verify-first'        => 'まずメールアドレスを確認してください。',
                'success'             => 'ログインに成功しました。',
            ],

            'logout' => [
                'no-login-agent' => 'ログインしているエージェントはいません。',
                'success'        => 'ログアウトに成功しました。',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => '入力されたパスワードが正しくありません。',
                    'success'          => 'アカウントが正常に更新されました。',
                ],
            ],
        ],

        'customers' => [
            'create-success' => '顧客が正常に作成されました！',
            'update-success' => '顧客が正常に更新されました！',
            'delete-success' => '顧客が正常に削除されました！',
            'delete-failed'  => '顧客の削除に失敗しました！',
            'pending-orders' => '顧客には保留中の注文があります！',
        ],

        'cart' => [
            'already-applied'     => 'クーポンは既に適用されています！',
            'coupon-applied'      => 'クーポンが正常に適用されました！',
            'coupon-removed'      => 'クーポンが正常に削除されました！',
            'create-success'      => 'カートが正常に作成されました！',
            'invalid-coupon'      => '無効なクーポンコードです！',
            'item-add-success'    => '商品がカートに正常に追加されました！',
            'item-remove-success' => '商品がカートから正常に削除されました！',
            'item-update-success' => '商品が正常に更新されました！',
            'not-found'           => 'カートが見つかりません！',
        ],

        'payment' => [
            'title' => 'Pos 支払い',

            'options' => [
                'cash' => [
                    'title'       => 'Pos 現金支払い',
                    'description' => 'これはPosの現金支払いです。',
                ],

                'card' => [
                    'title'       => 'Pos カード支払い',
                    'description' => 'これはPosのカード支払いです。',
                ],

                'split' => [
                    'title'       => 'Pos 分割支払い',
                    'description' => 'これはPosの分割支払いです。',
                ],
            ],

            'no-items' => '支払いを進めるためのカート内にアイテムがありません。',
            'success'  => '支払いが成功しました！',
        ],

        'shipping' => [
            'title'       => 'Pos 配送',
            'description' => 'これはPosの無料配送です。',
        ],

        'order' => [
            'sync-success' => '注文が正常に同期されました！',
        ],

        'return' => [
            'create-success'              => '返金が正常に作成されました',
            'creation-error'              => '返金の作成は許可されていません。',
            'invalid-qty'                 => '請求する商品の数量が無効です。',
            'invalid-refund-amount-error' => '返金金額はゼロ以上でなければなりません。',
            'refund-limit-error'          => '返金金額 :amount は処理できません。',
        ],

        'drawer' => [
            'create-success' => 'ドロワーが正常に開かれました！',
            'not-opened'     => 'ドロワーは開かれていません。',
            'close-success'  => 'ドロワーが正常に閉じられました！',
        ],

        'products' => [
            'request-success' => '商品リクエストが正常に送信されました。',
            'create-success'  => '商品が正常に作成されました！',
            'barcode-linked'  => 'バーコードが商品に紐付けられました。',
            'not-in-outlet'   => 'この商品はあなたのアウトレットに割り当てられていません。',
        ],

        'reports' => [
            'orders'                  => '注文',
            'average-order-value'     => '平均注文額',
            'average-items-per-order' => '1注文あたりの平均アイテム',
            'discounted-offers'       => '割引オファー',
            'cash-payments'           => '現金払い',
            'other-payments'          => 'その他の支払い',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => '注文メモ',
                    'note-not-available' => 'メモは利用できません！',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'Bagistoの販売時点管理（POS）拡張機能。',
                    'title' => '販売時点管理',

                    'settings' => [
                        'info'  => 'POSを有効にする、一般設定を行う、POS商品および請求書の受領設定。',
                        'title' => '設定',

                        'general' => [
                            'footer-content'       => 'フッターコンテンツ',
                            'footer-note'          => 'フッターノート',
                            'frontend-url'         => 'フロントエンドURL',
                            'heading-on-login'     => 'ログイン時の見出し',
                            'info'                 => '一般設定では、POSユーザーページの設定を行うことができ、ロゴ、見出し、フッターコンテンツ、フッターノートなどを追加できます。',
                            'pos-logo'             => 'POSロゴ',
                            'status'               => 'ステータス',
                            'sub-heading-on-login' => 'ログイン時のサブ見出し',
                            'title'                => '一般',
                        ],

                        'barcode' => [
                            'height'             => '高さ',
                            'hide-barcode'       => 'バーコードを隠す',
                            'info'               => 'バーコードの設定では、バーコード生成、バーコードの高さ、幅、バーコードタイプなどの設定が可能です。',
                            'prefix'             => 'プレフィックス',
                            'print-product-name' => '商品名を印刷する',
                            'title'              => 'バーコード',
                            'width'              => '幅',

                            'generate-with' => [
                                'title' => 'バーコードを生成する方法',

                                'options' => [
                                    'product-id' => '商品ID',
                                    'sku'        => '商品SKU',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'カスタム製品のSKUを許可',
                            'info'      => '製品設定はSKUの構成を可能にします。',
                            'title'     => '製品',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => '商品の割り当て',
            'banks'            => '銀行',
            'barcode-products' => 'バーコード商品',
            'create'           => '作成',
            'delete'           => '削除',
            'edit'             => '編集',
            'generate-barcode' => 'バーコードの生成',
            'orders'           => '注文',
            'outlets'          => 'アウトレット',
            'pos'              => '販売時点（POS）',
            'preview'          => 'プレビュー',
            'print-barcode'    => 'バーコードの印刷',
            'receipts'         => '領収書',
            'requests'         => 'リクエスト',
            'sales-report'     => '売上報告書',
            'users'            => 'エージェント',
            'view'             => '表示',
        ],

        'layouts' => [
            'banks'            => '銀行',
            'barcode-products' => 'バーコード商品',
            'orders'           => '注文',
            'pos'              => '販売時点（POS）',
            'receipts'         => '領収書',
            'requests'         => 'リクエスト',
            'sales-report'     => '売上報告書',

            'users' => [
                'agents'   => 'エージェント',
                'outlets'  => 'アウトレット',
                'title'    => 'エージェント',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'エージェントの作成',
                    'pos-front'  => 'POSフロント',
                    'title'      => 'エージェント',

                    'datagrid' => [
                        'action'              => 'アクション',
                        'delete'              => '削除',
                        'edit'                => '編集',
                        'email'               => 'メール',
                        'full-name'           => '氏名',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => '選択されたエージェントが正常に削除されました!',
                        'mass-update-success' => '選択されたエージェントが正常に更新されました!',
                        'outlet-name'         => '販売店名',
                        'profile-image'       => 'プロフィール画像',
                        'update-status'       => 'ステータスを更新',
                        'username'            => 'ユーザー名',

                        'status' => [
                            'title' => 'ステータス',

                            'options' => [
                                'active'  => 'アクティブ',
                                'disable' => '無効',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => '戻る',
                    'confirm-password'  => 'パスワードの確認',
                    'email'             => 'メール',
                    'first-name'        => '名',
                    'general'           => '一般',
                    'image'             => '画像',
                    'last-name'         => '姓',
                    'outlet'            => 'アウトレット',
                    'outlet-and-status' => 'アウトレットとステータス',
                    'password'          => 'パスワード',
                    'save-btn'          => 'エージェントの保存',
                    'select-outlet'     => 'アウトレットの選択',
                    'status'            => 'ステータス',
                    'title'             => 'エージェントの追加',
                    'user-image'        => 'エージェント画像のアップロード',
                    'username'          => 'ユーザー名',
                ],

                'edit' => [
                    'back-btn'          => '戻る',
                    'confirm-password'  => 'パスワードの確認',
                    'email'             => 'メール',
                    'first-name'        => '名',
                    'general'           => '一般',
                    'image'             => '画像',
                    'last-name'         => '姓',
                    'outlet'            => 'アウトレット',
                    'outlet-and-status' => 'アウトレットとステータス',
                    'password'          => 'パスワード',
                    'save-btn'          => 'エージェントの保存',
                    'select-outlet'     => 'アウトレットの選択',
                    'status'            => 'ステータス',
                    'title'             => 'エージェントの編集',
                    'user-image'        => 'エージェント画像のアップロード',
                    'username'          => 'ユーザー名',
                ],

                'create-success' => 'エージェントが正常に作成されました！',
                'delete-failed'  => 'エージェントの削除に失敗しました！',
                'delete-success' => 'エージェントが正常に削除されました！',
                'update-success' => 'エージェントが正常に更新されました！',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'アウトレットの作成',
                    'pos-front'  => 'POSフロント',
                    'title'      => 'アウトレット',

                    'datagrid' => [
                        'action'              => 'アクション',
                        'active'              => 'アクティブ',
                        'assign'              => '商品を割り当て',
                        'delete'              => '削除',
                        'edit'                => '編集',
                        'id'                  => 'ID',
                        'inactive'            => '非アクティブ',
                        'inventory-source'    => '在庫ソース',
                        'mass-delete-success' => '選択した店舗が正常に削除されました！',
                        'mass-update-success' => '選択した店舗が正常に更新されました！',
                        'name'                => '名前',
                        'receipt-title'       => 'レシートタイトル',
                        'status'              => 'ステータス',
                        'title'               => 'POSストアリスト',
                        'update-status'       => 'ステータスの更新',
                    ],
                ],

                'create' => [
                    'address'                 => '住所',
                    'back-btn'                => '戻る',
                    'btn-title'               => 'アウトレットを保存',
                    'city'                    => '市',
                    'country'                 => '国',
                    'customer-care-number'    => 'カスタマーケア番号',
                    'email'                   => 'メール',
                    'general'                 => '一般',
                    'gst-number'              => 'GST番号',
                    'inventory'               => '在庫',
                    'inventory-source'        => '在庫ソース',
                    'low-stock-qty'           => '低在庫数量',
                    'name'                    => 'アウトレット名',
                    'phone'                   => '電話',
                    'postcode'                => '郵便番号',
                    'receipt'                 => 'レシート',
                    'select-country'          => '国を選択',
                    'select-inventory-source' => '在庫ソースを選択',
                    'select-receipt'          => 'レシートを選択',
                    'state'                   => '州',
                    'status'                  => 'ステータス',
                    'store-address'           => 'アウトレット住所',
                    'title'                   => 'アウトレットを追加',
                    'website'                 => 'ウェブサイト',
                ],

                'edit' => [
                    'address'                 => '住所',
                    'back-btn'                => '戻る',
                    'btn-title'               => 'アウトレットを保存',
                    'city'                    => '市',
                    'country'                 => '国',
                    'customer-care-number'    => 'カスタマーケア番号',
                    'email'                   => 'メール',
                    'general'                 => '一般',
                    'gst-number'              => 'GST番号',
                    'inventory'               => '在庫',
                    'inventory-source'        => '在庫ソース',
                    'low-stock-qty'           => '低在庫数量',
                    'name'                    => 'アウトレット名',
                    'phone'                   => '電話',
                    'postcode'                => '郵便番号',
                    'receipt'                 => 'レシート',
                    'select-country'          => '国を選択',
                    'select-inventory-source' => '在庫ソースを選択',
                    'select-receipt'          => 'レシートを選択',
                    'state'                   => '州',
                    'status'                  => 'ステータス',
                    'store-address'           => 'アウトレット住所',
                    'title'                   => 'アウトレットを編集',
                    'website'                 => 'ウェブサイト',
                ],

                'assign' => [
                    'back-btn' => '戻る',
                    'title'    => 'アウトレット商品管理',

                    'datagrid' => [
                        'active'              => 'アクティブ',
                        'assign'              => '割り当て',
                        'disable'             => '無効',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'image'               => '画像',
                        'mass-assign-success' => '商品の割り当てが正常に更新されました！',
                        'name'                => '名前',
                        'out-of-stock'        => '在庫切れ',
                        'pos-status'          => 'POSステータス',
                        'price'               => '価格',
                        'product-image'       => '商品画像',
                        'qty'                 => '数量',
                        'qty-value'           => ':qty 利用可能',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'ステータス',
                        'type'                => 'タイプ',
                        'unassign'            => '割り当て解除',
                        'update-assign'       => '割り当ての更新',
                    ],
                ],

                'create-success' => 'アウトレットが正常に作成されました！',
                'delete-failed'  => 'アウトレットの削除に失敗しました！',
                'delete-success' => 'アウトレットが正常に削除されました！',
                'update-success' => 'アウトレットが正常に更新されました！',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'バーコード商品',

                'datagrid' => [
                    'barcode'               => 'バーコード',
                    'generate-barcode'      => 'バーコードの生成',
                    'print-barcode'         => 'バーコードの印刷',
                    'id'                    => 'ID',
                    'id-value'              => 'ID - :id',
                    'image'                 => '画像',
                    'mass-generate-success' => '選択した商品のバーコードが正常に生成されました！',
                    'name'                  => '名前',
                    'out-of-stock'          => '在庫切れ',
                    'price'                 => '価格',
                    'product-image'         => '商品画像',
                    'qty'                   => '数量',
                    'qty-value'             => ':qty 利用可能',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'ステータス',

                        'options' => [
                            'active'  => 'アクティブ',
                            'disable' => '無効',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => '戻る',
                'btn-title' => '印刷',
                'qty'       => '数量',
                'title'     => 'バーコードの印刷',
            ],

            'generate-failed'  => 'バーコードの生成に失敗しました！',
            'generate-success' => 'バーコードが正常に生成されました！',
        ],

        'orders' => [
            'index' => [
                'title' => '注文',

                'datagrid' => [
                    'customer-name' => '顧客名',
                    'grand-total'   => '合計',
                    'order-date'    => '注文日',
                    'order-id'      => '注文ID',
                    'order-ref-id'  => '注文参照ID',
                    'view'          => '表示',

                    'status' => [
                        'title' => 'ステータス',

                        'options' => [
                            'canceled'        => 'キャンセル',
                            'closed'          => '閉店',
                            'completed'       => '完了',
                            'fraud'           => '詐欺',
                            'pending'         => '保留中',
                            'pending-payment' => '支払い待ち',
                            'processing'      => '処理中',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'リクエスト',

                'datagrid' => [
                    'id'                  => 'ID',
                    'product-image'       => '商品画像',
                    'mass-update-error'   => 'リクエストの更新に失敗しました！',
                    'mass-update-success' => '選択したリクエストが正常に更新されました！',
                    'product-name'        => '商品名',
                    'outlet-name'         => 'アウトレット名',
                    'qty-value'           => '数量 - :qty',
                    'request-date'        => 'リクエスト日',
                    'requested-qty'       => 'リクエスト数量',
                    'update-status'       => 'ステータスの更新',
                    'user-name'           => 'ユーザー名',

                    'status' => [
                        'title' => 'ステータス',

                        'options' => [
                            'complete' => '完了',
                            'decline'  => '拒否',
                            'pending'  => '保留中',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => '戻る',
                'btn-title' => '保存',
                'title'     => 'リクエストされた商品詳細 #:id',

                'user-info' => [
                    'email'            => 'メール',
                    'name'             => '名前',
                    'outlet-address'   => 'アウトレット住所',
                    'outlet-inventory' => 'アウトレット在庫ソース',
                    'outlet-name'      => 'アウトレット名',
                    'title'            => 'ユーザー情報',
                ],

                'request-info' => [
                    'comment'       => 'コメント',
                    'product-name'  => '商品名',
                    'qty-value'     => '数量 - :qty',
                    'request-date'  => 'リクエスト日',
                    'requested-qty' => 'リクエスト数量',
                    'title'         => 'リクエスト情報',

                    'status' => [
                        'title' => 'ステータス',

                        'options' => [
                            'complete' => '完了',
                            'decline'  => '拒否',
                            'pending'  => '保留中',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'リクエストの更新に失敗しました！',
            'update-success' => 'リクエストが正常に更新されました！',
        ],

        'banks' => [
            'index' => [
                'btn-title' => '銀行を作成',
                'title'     => '銀行',

                'datagrid' => [
                    'active'              => 'アクティブ',
                    'address'             => '銀行の住所',
                    'agent-name'          => 'エージェント',
                    'delete'              => '削除',
                    'disable'             => '無効',
                    'id'                  => 'ID',
                    'mass-delete-success' => '選択された銀行が正常に削除されました！',
                    'name'                => '銀行名',
                    'status'              => 'ステータス',
                ],
            ],

            'create' => [
                'back-btn'  => '戻る',
                'btn-title' => '銀行を保存',
                'title'     => '新しい銀行を作成',

                'general' => [
                    'address' => '住所',
                    'email'   => 'メール',
                    'name'    => '名前',
                    'phone'   => '電話',
                    'title'   => '一般',
                ],

                'agent-and-status' => [
                    'agent'        => 'POSエージェントの割り当て',
                    'bank-status'  => '銀行ステータス',
                    'select-agent' => 'エージェントを選択',
                    'title'        => 'POSエージェント & 銀行ステータス',
                ],
            ],

            'edit' => [
                'back-btn'  => '戻る',
                'btn-title' => '銀行を保存',
                'title'     => '銀行を編集',

                'general' => [
                    'address' => '住所',
                    'email'   => 'メール',
                    'name'    => '名前',
                    'phone'   => '電話',
                    'title'   => '一般',
                ],

                'agent-and-status' => [
                    'agent'        => 'POSエージェントの割り当て',
                    'bank-status'  => '銀行ステータス',
                    'select-agent' => 'エージェントを選択',
                    'title'        => 'POSエージェント & 銀行ステータス',
                ],
            ],

            'create-success' => '銀行が正常に作成されました！',
            'delete-failed'  => '銀行の削除に失敗しました！',
            'delete-success' => '銀行が正常に削除されました！',
            'update-success' => '銀行が正常に更新されました！',
        ],

        'sales-reports' => [
            'index' => [
                'title' => '売上報告書',

                'datagrid' => [
                    'bank-name'      => '銀行名',
                    'grand-total'    => '合計',
                    'order-date'     => '注文日',
                    'order-id'       => '注文ID',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => '注文メモ',
                    'outlet-name'    => 'アウトレット名',
                    'payment-method' => '支払い方法',
                    'view'           => '表示',

                    'status' => [
                        'title' => 'ステータス',

                        'options' => [
                            'canceled'        => 'キャンセル',
                            'closed'          => '閉店',
                            'completed'       => '完了',
                            'fraud'           => '詐欺',
                            'pending'         => '保留中',
                            'pending-payment' => '支払い待ち',
                            'processing'      => '処理中',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'レシートを作成',
                'title'      => 'レシート',

                'datagrid' => [
                    'delete'              => '削除',
                    'edit'                => '編集',
                    'id'                  => 'ID',
                    'mass-delete-success' => '選択したレシートが正常に削除されました！',
                    'preview'             => 'プレビュー',
                    'title'               => 'タイトル',

                    'status' => [
                        'title' => 'ステータス',

                        'options' => [
                            'active'   => 'アクティブ',
                            'inactive' => '非アクティブ',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => '戻る',
                'btn-title' => 'レシートを保存',
                'title'     => '新しいレシートを作成',

                'general' => [
                    'cashier-name-label'      => 'レジ係名ラベル',
                    'change-amount-label'     => 'お釣り金額ラベル',
                    'credit-amount-label'     => 'クレジット金額ラベル',
                    'discount-amt-label'      => '割引金額ラベル',
                    'display-cashier-name'    => 'レジ係名を表示',
                    'display-change-amount'   => 'お釣り金額を表示',
                    'display-credit-amount'   => 'クレジット金額を表示',
                    'display-customer-name'   => '顧客名を表示',
                    'display-date'            => '日付を表示',
                    'display-discount-amt'    => '割引金額を表示',
                    'display-order-id'        => '注文IDを表示',
                    'display-outlet-address'  => 'アウトレット住所を表示',
                    'display-outlet-name'     => 'アウトレット名を表示',
                    'display-sub-total'       => '小計を表示',
                    'display-tax'             => '税金を表示',
                    'grand-total-label'       => '合計金額ラベル',
                    'order-id-label'          => '注文IDラベル',
                    'receipt-title'           => 'レシートタイトル',
                    'status'                  => 'ステータス',
                    'show-order-barcode'      => '注文バーコードを表示',
                    'show-print-confirmation' => '印刷確認を表示',
                    'sub-total-label'         => '小計ラベル',
                    'tax-label'               => '税金ラベル',
                    'title'                   => '一般',
                ],

                'logo' => [
                    'display-logo' => 'ロゴを表示',
                    'logo-alt'     => 'ロゴの代替テキスト',
                    'logo-height'  => 'ロゴの高さ (px)',
                    'logo-width'   => 'ロゴの幅 (px)',
                    'title'        => 'ロゴ',
                    'upload-logo'  => 'ロゴをアップロード',
                ],

                'header' => [
                    'header-content' => 'ヘッダー内容',
                    'title'          => 'ヘッダー',
                ],

                'footer' => [
                    'footer-content' => 'フッター内容',
                    'title'          => 'フッター',
                ],
            ],

            'edit' => [
                'back-btn'  => '戻る',
                'btn-title' => 'レシートを保存',
                'title'     => 'レシートを編集',

                'general' => [
                    'cashier-name-label'      => 'レジ係名ラベル',
                    'change-amount-label'     => 'お釣り金額ラベル',
                    'credit-amount-label'     => 'クレジット金額ラベル',
                    'discount-amt-label'      => '割引金額ラベル',
                    'display-cashier-name'    => 'レジ係名を表示',
                    'display-change-amount'   => 'お釣り金額を表示',
                    'display-credit-amount'   => 'クレジット金額を表示',
                    'display-customer-name'   => '顧客名を表示',
                    'display-date'            => '日付を表示',
                    'display-discount-amt'    => '割引金額を表示',
                    'display-order-id'        => '注文IDを表示',
                    'display-outlet-address'  => 'アウトレット住所を表示',
                    'display-outlet-name'     => 'アウトレット名を表示',
                    'display-sub-total'       => '小計を表示',
                    'display-tax'             => '税金を表示',
                    'grand-total-label'       => '合計金額ラベル',
                    'order-id-label'          => '注文IDラベル',
                    'receipt-title'           => 'レシートタイトル',
                    'show-order-barcode'      => '注文バーコードを表示',
                    'show-print-confirmation' => '印刷確認を表示',
                    'status'                  => 'ステータス',
                    'sub-total-label'         => '小計ラベル',
                    'tax-label'               => '税金ラベル',
                    'title'                   => '一般',
                ],

                'logo' => [
                    'display-logo' => 'ロゴを表示',
                    'logo-alt'     => 'ロゴの代替テキスト',
                    'logo-height'  => 'ロゴの高さ (px)',
                    'logo-width'   => 'ロゴの幅 (px)',
                    'title'        => 'ロゴ',
                    'upload-logo'  => 'ロゴをアップロード',
                ],

                'header' => [
                    'header-content' => 'ヘッダー内容',
                    'title'          => 'ヘッダー',
                ],

                'footer' => [
                    'footer-content' => 'フッター内容',
                    'title'          => 'フッター',
                ],
            ],

            'preview' => [
                'amount'         => '金額',
                'cashier'        => 'レジ係',
                'change-amount'  => 'お釣り',
                'customer'       => '顧客',
                'customer-email' => '顧客メール',
                'customer-name'  => '顧客名',
                'customer-phone' => '顧客電話',
                'date'           => '日付',
                'discount'       => '割引',
                'email'          => 'メール',
                'grand-total'    => '合計金額',
                'order-id'       => '注文ID',
                'phone'          => '電話',
                'price'          => '価格',
                'product'        => '商品',
                'qty'            => '数量',
                'sub-total'      => '小計',
                'tax'            => '税金',
                'title'          => 'レシートプレビュー',
                'total-qty'      => '合計数量',
            ],

            'create-success' => 'レシートが正常に作成されました！',
            'delete-failed'  => 'レシートの削除に失敗しました！',
            'delete-success' => 'レシートが正常に削除されました！',
            'update-success' => 'レシートが正常に更新されました！',
        ],
    ],

    'emails' => [
        'dear'     => ':name 様',
        'greeting' => 'こんにちは！',

        'registration' => [
            'message' => 'おめでとうございます！あなたのアカウントが正常に作成されました。ログインしてPOSシステムの使用を開始してください。',
            'subject' => 'POSユーザー登録メール',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'ログイン',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. 無断転載を禁じます。',
        ],
    ],
];
