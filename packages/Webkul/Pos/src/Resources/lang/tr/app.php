<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'Geçersiz kimlik bilgileri.',
                'not-activated'       => 'Hesabınız etkinleştirilmemiş.',
                'verify-first'        => 'Lütfen önce e-posta adresinizi doğrulayın.',
                'success'             => 'Başarıyla giriş yaptınız.',
            ],

            'logout' => [
                'no-login-agent' => 'Giriş yapmış bir ajan yok.',
                'success'        => 'Başarıyla çıkış yaptınız.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'Girdiğiniz şifre yanlış.',
                    'success'          => 'Hesabınız başarıyla güncellendi.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'Müşteri başarıyla oluşturuldu!',
            'update-success' => 'Müşteri başarıyla güncellendi!',
            'delete-success' => 'Müşteri başarıyla silindi!',
            'delete-failed'  => 'Müşteri silme işlemi başarısız oldu!',
            'pending-orders' => 'Müşterinin bekleyen siparişleri var!',
        ],

        'cart' => [
            'already-applied'     => 'Kupon zaten uygulandı!',
            'coupon-applied'      => 'Kupon başarıyla uygulandı!',
            'coupon-removed'      => 'Kupon başarıyla kaldırıldı!',
            'create-success'      => 'Sepet başarıyla oluşturuldu!',
            'invalid-coupon'      => 'Geçersiz kupon kodu!',
            'item-add-success'    => 'Ürün sepete başarıyla eklendi!',
            'item-remove-success' => 'Ürün sepetten başarıyla kaldırıldı!',
            'item-update-success' => 'Ürün başarıyla güncellendi!',
            'not-found'           => 'Sepet bulunamadı!',
        ],

        'payment' => [
            'title' => 'Pos Ödemesi',

            'options' => [
                'cash' => [
                    'title'       => 'Pos Nakit Ödemesi',
                    'description' => 'Bu, Pos nakit ödemesidir.',
                ],

                'card' => [
                    'title'       => 'Pos Kart Ödemesi',
                    'description' => 'Bu, Pos kart ödemesidir.',
                ],

                'split' => [
                    'title'       => 'Pos Bölünmüş Ödeme',
                    'description' => 'Bu, Pos bölünmüş ödemesidir.',
                ],
            ],

            'no-items' => 'Ödeme işlemi için sepetinizde ürün bulunmuyor.',
            'success'  => 'Ödeme başarıyla tamamlandı!',
        ],

        'shipping' => [
            'title'       => 'Pos Kargo',
            'description' => 'Bu, ücretsiz Pos kargosudur.',
        ],

        'order' => [
            'sync-success' => 'Sipariş başarıyla senkronize edildi!',
        ],

        'return' => [
            'create-success'              => 'İade başarıyla oluşturuldu',
            'creation-error'              => 'İade oluşturma izni yok.',
            'invalid-qty'                 => 'Faturalandırılacak ürünlerde geçersiz miktar bulundu.',
            'invalid-refund-amount-error' => 'İade tutarı sıfırdan büyük olmalıdır.',
            'refund-limit-error'          => 'İade tutarı :amount işlenemez.',
        ],

        'drawer' => [
            'create-success' => 'Çekmeceniz başarıyla açıldı!',
            'not-opened'     => 'Çekmece açılmadı.',
            'close-success'  => 'Çekmece başarıyla kapatıldı!',
        ],

        'products' => [
            'request-success' => 'Ürün talebi başarıyla gönderildi.',
            'create-success'  => 'Ürün başarıyla oluşturuldu!',
        ],

        'reports' => [
            'orders'                  => 'Siparişler',
            'average-order-value'     => 'Ortalama Sipariş Değeri',
            'average-items-per-order' => 'Sipariş Başına Ortalama Ürün',
            'discounted-offers'       => 'İndirimli Teklifler',
            'cash-payments'           => 'Nakit Ödemeler',
            'other-payments'          => 'Diğer Ödemeler',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'Sipariş notu',
                    'note-not-available' => 'Not mevcut değil!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'Bagisto Satış Noktası (POS) Uzantısı.',
                    'title' => 'Satış Noktası',

                    'settings' => [
                        'info'  => 'POS’u etkinleştir, Genel Yapılandırmayı Ayarla, POS Ürünleri ve Fatura Resepti.',
                        'title' => 'Ayarlar',

                        'general' => [
                            'footer-content'       => 'Alt Bilgi İçeriği',
                            'footer-note'          => 'Alt Bilgi Notu',
                            'frontend-url'         => 'Frontend URL',
                            'heading-on-login'     => 'Girişte Başlık',
                            'info'                 => 'Genel ayarlar, POS kullanıcı sayfası için logo, başlıklar, alt bilgi içeriği, alt bilgi notu vb. ekleyerek yapılandırmalar yapmanıza izin verir.',
                            'pos-logo'             => 'POS Logosu',
                            'status'               => 'Durum',
                            'sub-heading-on-login' => 'Girişte Alt Başlık',
                            'title'                => 'Genel',
                        ],

                        'barcode' => [
                            'height'             => 'Yükseklik',
                            'hide-barcode'       => 'Barkodu Gizle',
                            'info'               => 'Barkod ayarları, barkod üretimi, barkod yüksekliği, barkod genişliği, barkod türü vb. için yapılandırmaları yapmanıza olanak tanır.',
                            'prefix'             => 'Önek',
                            'print-product-name' => 'Ürün Adını Yazdır',
                            'title'              => 'Barkod',
                            'width'              => 'Genişlik',

                            'generate-with' => [
                                'title' => 'Barkod Oluşturma Şekli',

                                'options' => [
                                    'product-id' => 'Ürün ID',
                                    'sku'        => 'Ürün SKU',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'Özel ürün için SKU\'ya izin ver',
                            'info'      => 'Ürün ayarları, ürün SKU\'su için yapılandırmalara izin verir.',
                            'title'     => 'Ürünler',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'Ürünleri Ata',
            'banks'            => 'Bankalar',
            'barcode-products' => 'Barkodlu Ürünler',
            'create'           => 'Oluştur',
            'delete'           => 'Sil',
            'edit'             => 'Düzenle',
            'generate-barcode' => 'Barkod Oluştur',
            'orders'           => 'Siparişler',
            'outlets'          => 'Mağazalar',
            'pos'              => 'Satış Noktası (POS)',
            'preview'          => 'Önizleme',
            'print-barcode'    => 'Barkodu Yazdır',
            'receipts'         => 'Fişler',
            'requests'         => 'Talepler',
            'sales-report'     => 'Satış Raporu',
            'users'            => 'Ajanlar',
            'view'             => 'Görüntüle',
        ],

        'layouts' => [
            'banks'            => 'Bankalar',
            'barcode-products' => 'Barkodlu Ürünler',
            'orders'           => 'Siparişler',
            'pos'              => 'Satış Noktası (POS)',
            'receipts'         => 'Fişler',
            'requests'         => 'Talepler',
            'sales-report'     => 'Satış Raporu',

            'users' => [
                'agents'   => 'Ajanlar',
                'outlets'  => 'Mağazalar',
                'title'    => 'Ajanlar',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'Ajan Oluştur',
                    'pos-front'  => 'POS Front',
                    'title'      => 'Ajanslar',

                    'datagrid' => [
                        'action'              => 'Eylem',
                        'delete'              => 'Sil',
                        'edit'                => 'Düzenle',
                        'email'               => 'E-posta',
                        'full-name'           => 'Tam Ad',
                        'id'                  => 'Kimlik',
                        'id-value'            => 'Kimlik - :id',
                        'mass-delete-success' => 'Seçilen temsilciler başarıyla silindi!',
                        'mass-update-success' => 'Seçilen temsilciler başarıyla güncellendi!',
                        'outlet-name'         => 'Çıkış Adı',
                        'profile-image'       => 'Profil Resmi',
                        'update-status'       => 'Durumu Güncelle',
                        'username'            => 'Kullanıcı adı',

                        'status' => [
                            'title' => 'Durum',

                            'options' => [
                                'active'  => 'Aktif',
                                'disable' => 'Devre Dışı',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'Geri',
                    'confirm-password'  => 'Şifreyi Onayla',
                    'email'             => 'E-posta',
                    'first-name'        => 'Ad',
                    'general'           => 'Genel',
                    'image'             => 'Resim',
                    'last-name'         => 'Soyad',
                    'outlet'            => 'Mağaza',
                    'outlet-and-status' => 'Mağaza & Durum',
                    'password'          => 'Şifre',
                    'save-btn'          => 'Ajanı Kaydet',
                    'select-outlet'     => 'Mağaza Seç',
                    'status'            => 'Durum',
                    'title'             => 'Ajan Ekle',
                    'user-image'        => 'Ajan Resmi Yükle',
                    'username'          => 'Kullanıcı Adı',
                ],

                'edit' => [
                    'back-btn'          => 'Geri',
                    'confirm-password'  => 'Şifreyi Onayla',
                    'email'             => 'E-posta',
                    'first-name'        => 'Ad',
                    'general'           => 'Genel',
                    'image'             => 'Resim',
                    'last-name'         => 'Soyad',
                    'outlet'            => 'Mağaza',
                    'outlet-and-status' => 'Mağaza & Durum',
                    'password'          => 'Şifre',
                    'save-btn'          => 'Ajanı Kaydet',
                    'select-outlet'     => 'Mağaza Seç',
                    'status'            => 'Durum',
                    'title'             => 'Ajansı Düzenle',
                    'user-image'        => 'Ajan Resmi Yükle',
                    'username'          => 'Kullanıcı Adı',
                ],

                'create-success' => 'Ajan başarıyla oluşturuldu!',
                'delete-failed'  => 'Ajan silme başarısız!',
                'delete-success' => 'Ajan başarıyla silindi!',
                'update-success' => 'Ajan başarıyla güncellendi!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'Mağaza Oluştur',
                    'pos-front'  => 'POS Front',
                    'title'      => 'Mağazalar',

                    'datagrid' => [
                        'action'              => 'Eylem',
                        'active'              => 'Aktif',
                        'assign'              => 'Ürün Ata',
                        'delete'              => 'Sil',
                        'edit'                => 'Düzenle',
                        'id'                  => 'ID',
                        'inactive'            => 'Pasif',
                        'inventory-source'    => 'Envanter Kaynağı',
                        'mass-delete-success' => 'Seçilen mağazalar başarıyla silindi!',
                        'mass-update-success' => 'Seçilen mağazalar başarıyla güncellendi!',
                        'name'                => 'Ad',
                        'receipt-title'       => 'Fiş Başlığı',
                        'status'              => 'Durum',
                        'title'               => 'Pos Mağaza Listesi',
                        'update-status'       => 'Durumu Güncelle',
                    ],
                ],

                'create' => [
                    'address'                 => 'Adres',
                    'back-btn'                => 'Geri',
                    'btn-title'               => 'Mağazayı Kaydet',
                    'city'                    => 'Şehir',
                    'country'                 => 'Ülke',
                    'customer-care-number'    => 'Müşteri Hizmetleri Numarası',
                    'email'                   => 'E-posta',
                    'general'                 => 'Genel',
                    'gst-number'              => 'GST Numarası',
                    'inventory'               => 'Envanter',
                    'inventory-source'        => 'Envanter Kaynağı',
                    'low-stock-qty'           => 'Düşük Stok Miktarı',
                    'name'                    => 'Mağaza Adı',
                    'phone'                   => 'Telefon',
                    'postcode'                => 'Posta Kodu',
                    'receipt'                 => 'Fiş',
                    'select-country'          => 'Ülke Seç',
                    'select-inventory-source' => 'Envanter Kaynağı Seç',
                    'select-receipt'          => 'Fiş Seç',
                    'state'                   => 'Eyalet',
                    'status'                  => 'Durum',
                    'store-address'           => 'Mağaza Adresi',
                    'title'                   => 'Mağaza Ekle',
                    'website'                 => 'Web Sitesi',
                ],

                'edit' => [
                    'address'                 => 'Adres',
                    'back-btn'                => 'Geri',
                    'btn-title'               => 'Mağazayı Kaydet',
                    'city'                    => 'Şehir',
                    'country'                 => 'Ülke',
                    'customer-care-number'    => 'Müşteri Hizmetleri Numarası',
                    'email'                   => 'E-posta',
                    'general'                 => 'Genel',
                    'gst-number'              => 'GST Numarası',
                    'inventory'               => 'Envanter',
                    'inventory-source'        => 'Envanter Kaynağı',
                    'low-stock-qty'           => 'Düşük Stok Miktarı',
                    'name'                    => 'Mağaza Adı',
                    'phone'                   => 'Telefon',
                    'postcode'                => 'Posta Kodu',
                    'receipt'                 => 'Fiş',
                    'select-country'          => 'Ülke Seç',
                    'select-inventory-source' => 'Envanter Kaynağı Seç',
                    'select-receipt'          => 'Fiş Seç',
                    'state'                   => 'Eyalet',
                    'status'                  => 'Durum',
                    'store-address'           => 'Mağaza Adresi',
                    'title'                   => 'Mağazayı Düzenle',
                    'website'                 => 'Web Sitesi',
                ],

                'assign' => [
                    'back-btn' => 'Geri',
                    'title'    => 'Mağaza Ürünlerini Yönet',

                    'datagrid' => [
                        'active'              => 'Aktif',
                        'assign'              => 'Ata',
                        'disable'             => 'Devre Dışı',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'image'               => 'Resim',
                        'mass-assign-success' => 'Ürün atama başarıyla güncellendi!',
                        'name'                => 'Ad',
                        'out-of-stock'        => 'Stokta Yok',
                        'pos-status'          => 'POS Durumu',
                        'price'               => 'Fiyat',
                        'product-image'       => 'Ürün Resmi',
                        'qty'                 => 'Miktar',
                        'qty-value'           => ':qty Mevcut',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'Durum',
                        'type'                => 'Tür',
                        'unassign'            => 'Atamayı Kaldır',
                        'update-assign'       => 'Atamayı Güncelle',
                    ],
                ],

                'create-success' => 'Mağaza başarıyla oluşturuldu!',
                'delete-failed'  => 'Mağaza silme başarısız!',
                'delete-success' => 'Mağaza başarıyla silindi!',
                'update-success' => 'Mağaza başarıyla güncellendi!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Barkodlu Ürünler',

                'datagrid' => [
                    'barcode'               => 'Barkod',
                    'generate-barcode'      => 'Barkod Oluştur',
                    'print-barcode'         => 'Barkodu Yazdır',
                    'id'                    => 'ID',
                    'id-value'              => 'ID - :id',
                    'image'                 => 'Resim',
                    'mass-generate-success' => 'Seçilen ürünlerin barkodu başarıyla oluşturuldu!',
                    'name'                  => 'Ad',
                    'out-of-stock'          => 'Stokta Yok',
                    'price'                 => 'Fiyat',
                    'product-image'         => 'Ürün Resmi',
                    'qty'                   => 'Miktar',
                    'qty-value'             => ':qty Mevcut',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'Durum',

                        'options' => [
                            'active'  => 'Aktif',
                            'disable' => 'Devre Dışı',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'Geri',
                'btn-title' => 'Yazdır',
                'qty'       => 'Miktar',
                'title'     => 'Barkodu Yazdır',
            ],

            'generate-failed'  => 'Barkod oluşturma başarısız!',
            'generate-success' => 'Barkod başarıyla oluşturuldu!',
        ],

        'orders' => [
            'index' => [
                'title' => 'Siparişler',

                'datagrid' => [
                    'customer-name' => 'Müşteri Adı',
                    'grand-total'   => 'Toplam Tutar',
                    'order-date'    => 'Sipariş Tarihi',
                    'order-id'      => 'Sipariş ID',
                    'order-ref-id'  => 'Sipariş Ref. ID',
                    'view'          => 'Görüntüle',

                    'status' => [
                        'title' => 'Durum',

                        'options' => [
                            'canceled'        => 'İptal Edildi',
                            'closed'          => 'Kapalı',
                            'completed'       => 'Tamamlandı',
                            'fraud'           => 'Dolandırıcılık',
                            'pending'         => 'Beklemede',
                            'pending-payment' => 'Ödeme Bekleniyor',
                            'processing'      => 'İşleniyor',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'Talepler',

                'datagrid' => [
                    'id'                  => 'ID',
                    'product-image'       => 'Ürün Resmi',
                    'mass-update-error'   => 'Talep güncelleme başarısız!',
                    'mass-update-success' => 'Seçilen talepler başarıyla güncellendi!',
                    'product-name'        => 'Ürün Adı',
                    'outlet-name'         => 'Mağaza Adı',
                    'qty-value'           => 'Miktar - :qty',
                    'request-date'        => 'Talep Tarihi',
                    'requested-qty'       => 'Talep Edilen Miktar',
                    'update-status'       => 'Durumu Güncelle',
                    'user-name'           => 'Kullanıcı Adı',

                    'status' => [
                        'title' => 'Durum',

                        'options' => [
                            'complete' => 'Tamamlandı',
                            'decline'  => 'Reddet',
                            'pending'  => 'Beklemede',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'Geri',
                'btn-title' => 'Kaydet',
                'title'     => 'Talep Edilen Ürün Detayları #:id',

                'user-info' => [
                    'email'            => 'E-posta',
                    'name'             => 'Ad',
                    'outlet-address'   => 'Mağaza Adresi',
                    'outlet-inventory' => 'Mağaza Envanter Kaynağı',
                    'outlet-name'      => 'Mağaza Adı',
                    'title'            => 'Kullanıcı Bilgileri',
                ],

                'request-info' => [
                    'comment'       => 'Yorum',
                    'product-name'  => 'Ürün Adı',
                    'qty-value'     => 'Miktar - :qty',
                    'request-date'  => 'Talep Tarihi',
                    'requested-qty' => 'Talep Edilen Miktar',
                    'title'         => 'Talep Bilgileri',

                    'status' => [
                        'title' => 'Durum',

                        'options' => [
                            'complete' => 'Tamamlandı',
                            'decline'  => 'Reddet',
                            'pending'  => 'Beklemede',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'Talep güncelleme başarısız!',
            'update-success' => 'Talep başarıyla güncellendi!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'Banka Oluştur',
                'title'     => 'Bankalar',

                'datagrid' => [
                    'active'              => 'Aktif',
                    'address'             => 'Banka Adresi',
                    'agent-name'          => 'Ajan',
                    'delete'              => 'Sil',
                    'disable'             => 'Devre Dışı Bırak',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Seçilen bankalar başarıyla silindi!',
                    'name'                => 'Banka Adı',
                    'status'              => 'Durum',
                ],
            ],

            'create' => [
                'back-btn'  => 'Geri',
                'btn-title' => 'Bankayı Kaydet',
                'title'     => 'Yeni Banka Oluştur',

                'general' => [
                    'address' => 'Adres',
                    'email'   => 'E-posta',
                    'name'    => 'Ad',
                    'phone'   => 'Telefon',
                    'title'   => 'Genel',
                ],

                'agent-and-status' => [
                    'agent'        => 'POS Ajanı Ata',
                    'bank-status'  => 'Banka Durumu',
                    'select-agent' => 'Ajan Seç',
                    'title'        => 'POS Ajanı & Banka Durumu',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Geri',
                'btn-title' => 'Bankayı Kaydet',
                'title'     => 'Bankayı Düzenle',

                'general' => [
                    'address' => 'Adres',
                    'email'   => 'E-posta',
                    'name'    => 'Ad',
                    'phone'   => 'Telefon',
                    'title'   => 'Genel',
                ],

                'agent-and-status' => [
                    'agent'        => 'POS Ajanı Ata',
                    'bank-status'  => 'Banka Durumu',
                    'select-agent' => 'Ajan Seç',
                    'title'        => 'POS Ajanı & Banka Durumu',
                ],
            ],

            'create-success' => 'Banka başarıyla oluşturuldu!',
            'delete-failed'  => 'Banka silme başarısız!',
            'delete-success' => 'Banka başarıyla silindi!',
            'update-success' => 'Banka başarıyla güncellendi!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'Satış Raporu',

                'datagrid' => [
                    'bank-name'      => 'Banka Adı',
                    'grand-total'    => 'Toplam Tutar',
                    'order-date'     => 'Sipariş Tarihi',
                    'order-id'       => 'Sipariş ID',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'Sipariş Notu',
                    'outlet-name'    => 'Mağaza Adı',
                    'payment-method' => 'Ödeme Yöntemi',
                    'view'           => 'Görüntüle',

                    'status' => [
                        'title' => 'Durum',

                        'options' => [
                            'canceled'        => 'İptal Edildi',
                            'closed'          => 'Kapalı',
                            'completed'       => 'Tamamlandı',
                            'fraud'           => 'Dolandırıcılık',
                            'pending'         => 'Beklemede',
                            'pending-payment' => 'Ödeme Bekleniyor',
                            'processing'      => 'İşleniyor',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'Makbuz Oluştur',
                'title'      => 'Makbuzlar',

                'datagrid' => [
                    'delete'              => 'Sil',
                    'edit'                => 'Düzenle',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Seçilen makbuzlar başarıyla silindi!',
                    'preview'             => 'Önizleme',
                    'title'               => 'Başlık',

                    'status' => [
                        'title' => 'Durum',

                        'options' => [
                            'active'   => 'Aktif',
                            'inactive' => 'Pasif',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'Geri',
                'btn-title' => 'Makbuzu Kaydet',
                'title'     => 'Yeni Makbuz Oluştur',

                'general' => [
                    'cashier-name-label'      => 'Kasiyer Adı Etiketi',
                    'change-amount-label'     => 'Para Üstü Etiketi',
                    'credit-amount-label'     => 'Kredi Miktarı Etiketi',
                    'discount-amt-label'      => 'İndirim Miktarı Etiketi',
                    'display-cashier-name'    => 'Kasiyer Adını Göster',
                    'display-change-amount'   => 'Para Üstü Miktarını Göster',
                    'display-credit-amount'   => 'Kredi Miktarını Göster',
                    'display-customer-name'   => 'Müşteri Adını Göster',
                    'display-date'            => 'Tarihi Göster',
                    'display-discount-amt'    => 'İndirim Miktarını Göster',
                    'display-order-id'        => 'Sipariş ID\'sini Göster',
                    'display-outlet-address'  => 'Mağaza Adresini Göster',
                    'display-outlet-name'     => 'Mağaza Adını Göster',
                    'display-sub-total'       => 'Ara Toplamı Göster',
                    'display-tax'             => 'Vergiyi Göster',
                    'grand-total-label'       => 'Toplam Tutar Etiketi',
                    'order-id-label'          => 'Sipariş ID Etiketi',
                    'receipt-title'           => 'Makbuz Başlığı',
                    'show-order-barcode'      => 'Sipariş barkodunu göster',
                    'show-print-confirmation' => 'Yazdırma onayını göster',
                    'status'                  => 'Durum',
                    'sub-total-label'         => 'Ara Toplam Etiketi',
                    'tax-label'               => 'Vergi Etiketi',
                    'title'                   => 'Genel',
                ],

                'logo' => [
                    'display-logo' => 'Logoyu Göster',
                    'logo-alt'     => 'Logo Alt Metni',
                    'logo-height'  => 'Logo Yüksekliği (px cinsinden)',
                    'logo-width'   => 'Logo Genişliği (px cinsinden)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Logo Yükle',
                ],

                'header' => [
                    'header-content' => 'Başlık İçeriği',
                    'title'          => 'Başlık',
                ],

                'footer' => [
                    'footer-content' => 'Alt Bilgi İçeriği',
                    'title'          => 'Alt Bilgi',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Geri',
                'btn-title' => 'Makbuzu Kaydet',
                'title'     => 'Makbuzu Düzenle',

                'general' => [
                    'cashier-name-label'      => 'Kasiyer Adı Etiketi',
                    'change-amount-label'     => 'Para Üstü Etiketi',
                    'credit-amount-label'     => 'Kredi Miktarı Etiketi',
                    'discount-amt-label'      => 'İndirim Miktarı Etiketi',
                    'display-cashier-name'    => 'Kasiyer Adını Göster',
                    'display-change-amount'   => 'Para Üstü Miktarını Göster',
                    'display-credit-amount'   => 'Kredi Miktarını Göster',
                    'display-customer-name'   => 'Müşteri Adını Göster',
                    'display-date'            => 'Tarihi Göster',
                    'display-discount-amt'    => 'İndirim Miktarını Göster',
                    'display-order-id'        => 'Sipariş ID\'sini Göster',
                    'display-outlet-address'  => 'Mağaza Adresini Göster',
                    'display-outlet-name'     => 'Mağaza Adını Göster',
                    'display-sub-total'       => 'Ara Toplamı Göster',
                    'display-tax'             => 'Vergiyi Göster',
                    'grand-total-label'       => 'Toplam Tutar Etiketi',
                    'order-id-label'          => 'Sipariş ID Etiketi',
                    'receipt-title'           => 'Makbuz Başlığı',
                    'show-order-barcode'      => 'Sipariş barkodunu göster',
                    'show-print-confirmation' => 'Yazdırma onayını göster',
                    'status'                  => 'Durum',
                    'sub-total-label'         => 'Ara Toplam Etiketi',
                    'tax-label'               => 'Vergi Etiketi',
                    'title'                   => 'Genel',
                ],

                'logo' => [
                    'display-logo' => 'Logoyu Göster',
                    'logo-alt'     => 'Logo Alt Metni',
                    'logo-height'  => 'Logo Yüksekliği (px cinsinden)',
                    'logo-width'   => 'Logo Genişliği (px cinsinden)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Logo Yükle',
                ],

                'header' => [
                    'header-content' => 'Başlık İçeriği',
                    'title'          => 'Başlık',
                ],

                'footer' => [
                    'footer-content' => 'Alt Bilgi İçeriği',
                    'title'          => 'Alt Bilgi',
                ],
            ],

            'preview' => [
                'amount'         => 'Miktar',
                'cashier'        => 'Kasiyer',
                'change-amount'  => 'Para Üstü',
                'customer'       => 'Müşteri',
                'customer-email' => 'Müşteri E-posta',
                'customer-name'  => 'Müşteri Adı',
                'customer-phone' => 'Müşteri Telefonu',
                'date'           => 'Tarih',
                'discount'       => 'İndirim',
                'email'          => 'E-posta',
                'grand-total'    => 'Toplam Tutar',
                'order-id'       => 'Sipariş ID',
                'phone'          => 'Telefon',
                'price'          => 'Fiyat',
                'product'        => 'Ürün',
                'qty'            => 'Miktar',
                'sub-total'      => 'Ara Toplam',
                'tax'            => 'Vergi',
                'title'          => 'Makbuz Önizleme',
                'total-qty'      => 'Toplam Miktar',
            ],

            'create-success' => 'Makbuz başarıyla oluşturuldu!',
            'delete-failed'  => 'Makbuz silme başarısız!',
            'delete-success' => 'Makbuz başarıyla silindi!',
            'update-success' => 'Makbuz başarıyla güncellendi!',
        ],
    ],

    'emails' => [
        'dear'     => 'Sayın :name',
        'greeting' => 'Merhaba!',

        'registration' => [
            'message' => 'Tebrikler! Hesabınız başarıyla oluşturuldu. Lütfen hesabınıza giriş yapın ve POS sistemini kullanmaya başlayın.',
            'subject' => 'POS Kullanıcı Kaydı Maili',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'Giriş Yap',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
