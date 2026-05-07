<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'Credencials no vàlides.',
                'not-activated'       => 'El vostre compte no està activat.',
                'verify-first'        => 'Si us plau, verifiqueu primer el vostre correu electrònic.',
                'success'             => 'Heu iniciat sessió amb èxit.',
            ],

            'logout' => [
                'no-login-agent' => 'No hi ha cap agent connectat.',
                'success'        => 'Heu tancat la sessió amb èxit.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'La contrasenya introduïda no és correcta.',
                    'success'          => 'El vostre compte s\'ha actualitzat correctament.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'Client creat amb èxit!',
            'update-success' => 'Client actualitzat amb èxit!',
            'delete-success' => 'Client eliminat amb èxit!',
            'delete-failed'  => 'No s\'ha pogut eliminar el client!',
            'pending-orders' => 'El client té comandes pendents!',
        ],

        'cart' => [
            'already-applied'     => 'El cupó ja s\'ha aplicat!',
            'coupon-applied'      => 'Cupó aplicat amb èxit!',
            'coupon-removed'      => 'Cupó eliminat amb èxit!',
            'create-success'      => 'Carret creat amb èxit!',
            'invalid-coupon'      => 'Codi de cupó no vàlid!',
            'item-add-success'    => 'Producte afegit al carret amb èxit!',
            'item-remove-success' => 'Producte eliminat del carret amb èxit!',
            'item-update-success' => 'Producte actualitzat amb èxit!',
            'not-found'           => 'Carret no trobat!',
        ],

        'payment' => [
            'title' => 'Pagament Pos',

            'options' => [
                'cash' => [
                    'title'       => 'Pagament en efectiu Pos',
                    'description' => 'Aquest és el pagament en efectiu Pos.',
                ],

                'card' => [
                    'title'       => 'Pagament amb targeta Pos',
                    'description' => 'Aquest és el pagament amb targeta Pos.',
                ],

                'split' => [
                    'title'       => 'Pagament dividit Pos',
                    'description' => 'Aquest és el pagament dividit Pos.',
                ],
            ],

            'no-items' => 'No hi ha articles al carret per continuar amb el pagament.',
            'success'  => 'Pagament completat amb èxit!',
        ],

        'shipping' => [
            'title'       => 'Enviament Pos',
            'description' => 'Aquest és l\'enviament gratuït Pos.',
        ],

        'order' => [
            'sync-success' => 'Comanda sincronitzada amb èxit!',
        ],

        'return' => [
            'create-success'              => 'Reemborsament creat amb èxit',
            'creation-error'              => 'No es permet crear un reemborsament.',
            'invalid-qty'                 => 'Hem trobat una quantitat no vàlida per facturar els articles.',
            'invalid-refund-amount-error' => 'L’import del reemborsament ha de ser superior a zero.',
            'refund-limit-error'          => 'No es pot processar l’import de reemborsament :amount.',
        ],

        'drawer' => [
            'create-success' => 'Calaix obert amb èxit!',
            'not-opened'     => 'El calaix no està obert.',
            'close-success'  => 'Calaix tancat amb èxit!',
        ],

        'products' => [
            'request-success' => 'Sol·licitud de producte enviada amb èxit.',
            'create-success'  => 'Producte creat amb èxit!',
        ],

        'reports' => [
            'orders'                  => 'Comandes',
            'average-order-value'     => 'Valor mitjà de la comanda',
            'average-items-per-order' => 'Articles mitjans per comanda',
            'discounted-offers'       => 'Ofertes amb descompte',
            'cash-payments'           => 'Pagaments en efectiu',
            'other-payments'          => 'Altres pagaments',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'Nota de la comanda',
                    'note-not-available' => 'Nota no disponible!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'L\'extensió Bagisto Point of Sale (POS).',
                    'title' => 'Punt de Venda',

                    'settings' => [
                        'info'  => 'Activa el POS, configura la configuració general, el producte POS i el rebut de la factura.',
                        'title' => 'Configuració',

                        'general' => [
                            'footer-content'       => 'Contingut del peu de pàgina',
                            'footer-note'          => 'Nota del peu de pàgina',
                            'frontend-url'         => 'URL del frontend',
                            'heading-on-login'     => 'Encapçalament en iniciar sessió',
                            'info'                 => 'La configuració general permet les configuracions per a la pàgina d\'usuari POS, afegint logotip, encapçalaments, continguts del peu de pàgina, nota del peu de pàgina, etc.',
                            'pos-logo'             => 'Logotip del POS',
                            'status'               => 'Estat',
                            'sub-heading-on-login' => 'Subencapçalament en iniciar sessió',
                            'title'                => 'General',
                        ],

                        'barcode' => [
                            'height'             => 'Alçada',
                            'hide-barcode'       => 'Amaga el codi de barres',
                            'info'               => 'La configuració del codi de barres permet les configuracions per a la generació de codis de barres, alçada, amplada, tipus de codi de barres, etc.',
                            'prefix'             => 'Prefix',
                            'print-product-name' => 'Imprimeix el nom del producte',
                            'title'              => 'Codi de barres',
                            'width'              => 'Amplada',

                            'generate-with' => [
                                'title' => 'Genera el codi de barres amb',

                                'options' => [
                                    'product-id' => 'Id del producte',
                                    'sku'        => 'SKU del producte',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'Permet SKU per al producte personalitzat',
                            'info'      => 'La configuració del producte permet les configuracions per a l\'SKU del producte.',
                            'title'     => 'Productes',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'Assignar Productes',
            'banks'            => 'Bancs',
            'barcode-products' => 'Productes amb Codi de Barres',
            'create'           => 'Crear',
            'delete'           => 'Eliminar',
            'edit'             => 'Editar',
            'generate-barcode' => 'Generar Codi de Barres',
            'orders'           => 'Comandes',
            'outlets'          => 'Botigues',
            'pos'              => 'Punt de Venda (POS)',
            'preview'          => 'Previsualitzar',
            'print-barcode'    => 'Imprimir Codi de Barres',
            'receipts'         => 'Rebuts',
            'requests'         => 'Sol·licituds',
            'sales-report'     => 'Informe de Vendes',
            'users'            => 'Agents',
            'view'             => 'Veure',
        ],

        'layouts' => [
            'banks'            => 'Bancs',
            'barcode-products' => 'Productes amb Codi de Barres',
            'orders'           => 'Comandes',
            'pos'              => 'Punt de Venda (POS)',
            'receipts'         => 'Rebuts',
            'requests'         => 'Sol·licituds',
            'sales-report'     => 'Informe de Vendes',

            'users' => [
                'agents'   => 'Agents',
                'outlets'  => 'Botigues',
                'title'    => 'Agents',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'Crear Agent',
                    'pos-front'  => 'Frontal POS',
                    'title'      => 'Agents',

                    'datagrid' => [
                        'action'              => 'Acció',
                        'delete'              => 'Eliminar',
                        'edit'                => 'Editar',
                        'email'               => 'Correu electrònic',
                        'full-name'           => 'Nom complet',
                        'id'                  => 'Id',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => 'Agents seleccionats eliminats amb èxit!',
                        'mass-update-success' => 'Agents seleccionats actualitzats amb èxit!',
                        'outlet-name'         => 'Nom de la botiga',
                        'profile-image'       => 'Imatge de perfil',
                        'update-status'       => 'Actualitzar estat',
                        'username'            => 'Nom d\'usuari',

                        'status' => [
                            'title' => 'Estat',

                            'options' => [
                                'active'  => 'Actiu',
                                'disable' => 'Desactivat',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'Enrere',
                    'confirm-password'  => 'Confirma la contrasenya',
                    'email'             => 'Correu electrònic',
                    'first-name'        => 'Nom',
                    'general'           => 'General',
                    'image'             => 'Imatge',
                    'last-name'         => 'Cognom',
                    'outlet'            => 'Botiga',
                    'outlet-and-status' => 'Botiga i Estat',
                    'password'          => 'Contrasenya',
                    'save-btn'          => 'Desa l\'Agent',
                    'select-outlet'     => 'Selecciona Botiga',
                    'status'            => 'Estat',
                    'title'             => 'Afegeix Agent',
                    'user-image'        => 'Carrega Imatge de l\'Agent',
                    'username'          => 'Nom d\'usuari',
                ],

                'edit' => [
                    'back-btn'          => 'Enrere',
                    'confirm-password'  => 'Confirma la contrasenya',
                    'email'             => 'Correu electrònic',
                    'first-name'        => 'Nom',
                    'general'           => 'General',
                    'image'             => 'Imatge',
                    'last-name'         => 'Cognom',
                    'outlet'            => 'Botiga',
                    'outlet-and-status' => 'Botiga i Estat',
                    'password'          => 'Contrasenya',
                    'save-btn'          => 'Desa l\'Agent',
                    'select-outlet'     => 'Selecciona Botiga',
                    'status'            => 'Estat',
                    'title'             => 'Edita Agent',
                    'user-image'        => 'Carrega Imatge de l\'Agent',
                    'username'          => 'Nom d\'usuari',
                ],

                'create-success' => 'Agent creat amb èxit!',
                'delete-failed'  => 'No s\'ha pogut eliminar l\'agent!',
                'delete-success' => 'Agent eliminat amb èxit!',
                'update-success' => 'Agent actualitzat amb èxit!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'Crear Botiga',
                    'pos-front'  => 'Frontal POS',
                    'title'      => 'Botigues',

                    'datagrid' => [
                        'action'              => 'Acció',
                        'active'              => 'Actiu',
                        'assign'              => 'Assigna Producte',
                        'delete'              => 'Eliminar',
                        'edit'                => 'Editar',
                        'id'                  => 'Id',
                        'inactive'            => 'Inactiu',
                        'inventory-source'    => 'Font d\'Inventari',
                        'mass-delete-success' => 'Botigues seleccionades eliminades amb èxit!',
                        'mass-update-success' => 'Botigues seleccionades actualitzades amb èxit!',
                        'name'                => 'Nom',
                        'receipt-title'       => 'Títol del Rebut',
                        'status'              => 'Estat',
                        'title'               => 'Llista de Botigues POS',
                        'update-status'       => 'Actualitzar Estat',
                    ],
                ],

                'create' => [
                    'address'                 => 'Adreça',
                    'back-btn'                => 'Enrere',
                    'btn-title'               => 'Desa Botiga',
                    'city'                    => 'Ciutat',
                    'country'                 => 'País',
                    'customer-care-number'    => 'Número d\'Atenció al Client',
                    'email'                   => 'Correu electrònic',
                    'general'                 => 'General',
                    'gst-number'              => 'Número GST',
                    'inventory'               => 'Inventari',
                    'inventory-source'        => 'Font d\'Inventari',
                    'low-stock-qty'           => 'Quantitat Baixa d\'Estoc',
                    'name'                    => 'Nom de la Botiga',
                    'phone'                   => 'Telèfon',
                    'postcode'                => 'Codi Postal',
                    'receipt'                 => 'Rebut',
                    'select-country'          => 'Selecciona País',
                    'select-inventory-source' => 'Selecciona Font d\'Inventari',
                    'select-receipt'          => 'Selecciona Rebut',
                    'state'                   => 'Estat',
                    'status'                  => 'Estat',
                    'store-address'           => 'Adreça de la Botiga',
                    'title'                   => 'Afegeix Botiga',
                    'website'                 => 'Lloc Web',
                ],

                'edit' => [
                    'address'                 => 'Adreça',
                    'back-btn'                => 'Enrere',
                    'btn-title'               => 'Desa Botiga',
                    'city'                    => 'Ciutat',
                    'country'                 => 'País',
                    'customer-care-number'    => 'Número d\'Atenció al Client',
                    'email'                   => 'Correu electrònic',
                    'general'                 => 'General',
                    'gst-number'              => 'Número GST',
                    'inventory'               => 'Inventari',
                    'inventory-source'        => 'Font d\'Inventari',
                    'low-stock-qty'           => 'Quantitat Baixa d\'Estoc',
                    'name'                    => 'Nom de la Botiga',
                    'phone'                   => 'Telèfon',
                    'postcode'                => 'Codi Postal',
                    'receipt'                 => 'Rebut',
                    'select-country'          => 'Selecciona País',
                    'select-inventory-source' => 'Selecciona Font d\'Inventari',
                    'select-receipt'          => 'Selecciona Rebut',
                    'state'                   => 'Estat',
                    'status'                  => 'Estat',
                    'store-address'           => 'Adreça de la Botiga',
                    'title'                   => 'Edita Botiga',
                    'website'                 => 'Lloc Web',
                ],

                'assign' => [
                    'back-btn' => 'Enrere',
                    'title'    => 'Gestiona Productes de la Botiga',

                    'datagrid' => [
                        'active'              => 'Actiu',
                        'assign'              => 'Assigna',
                        'disable'             => 'Desactiva',
                        'id'                  => 'Id',
                        'id-value'            => 'Id - :id',
                        'image'               => 'Imatge',
                        'mass-assign-success' => 'Assignació de productes actualitzada amb èxit!',
                        'name'                => 'Nom',
                        'out-of-stock'        => 'Sense Estoc',
                        'pos-status'          => 'Estat POS',
                        'price'               => 'Preu',
                        'product-image'       => 'Imatge del Producte',
                        'qty'                 => 'Quantitat',
                        'qty-value'           => ':qty Disponible',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'Estat',
                        'type'                => 'Tipus',
                        'unassign'            => 'Desassigna',
                        'update-assign'       => 'Actualitza Assignació',
                    ],
                ],

                'create-success' => 'Botiga creada amb èxit!',
                'delete-failed'  => 'No s\'ha pogut eliminar la botiga!',
                'delete-success' => 'Botiga eliminada amb èxit!',
                'update-success' => 'Botiga actualitzada amb èxit!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Productes amb Codi de Barres',

                'datagrid' => [
                    'barcode'               => 'Codi de Barres',
                    'generate-barcode'      => 'Generar Codi de Barres',
                    'print-barcode'         => 'Imprimir Codi de Barres',
                    'id'                    => 'Id',
                    'id-value'              => 'Id - :id',
                    'image'                 => 'Imatge',
                    'mass-generate-success' => 'Codis de barres dels productes seleccionats generats amb èxit!',
                    'name'                  => 'Nom',
                    'out-of-stock'          => 'Sense Estoc',
                    'price'                 => 'Preu',
                    'product-image'         => 'Imatge del Producte',
                    'qty'                   => 'Quantitat',
                    'qty-value'             => ':qty Disponible',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'Estat',

                        'options' => [
                            'active'  => 'Actiu',
                            'disable' => 'Desactivat',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'   => 'Enrere',
                'btn-title'  => 'Imprimir',
                'qty'        => 'Quantitat',
                'title'      => 'Imprimir Codi de Barres',
            ],

            'generate-failed'  => 'La generació del codi de barres ha fallat!',
            'generate-success' => 'Codi de barres generat amb èxit!',
        ],

        'orders' => [
            'index' => [
                'title' => 'Comandes',

                'datagrid' => [
                    'customer-name' => 'Nom del Client',
                    'grand-total'   => 'Total General',
                    'order-date'    => 'Data de la Comanda',
                    'order-id'      => 'ID de la Comanda',
                    'order-ref-id'  => 'ID de Ref. de la Comanda',
                    'view'          => 'Veure',

                    'status' => [
                        'title' => 'Estat',

                        'options' => [
                            'canceled'        => 'Cancel·lat',
                            'closed'          => 'Tancat',
                            'completed'       => 'Completat',
                            'fraud'           => 'Fraudulent',
                            'pending'         => 'Pendent',
                            'pending-payment' => 'Pagament Pendent',
                            'processing'      => 'Processant',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'Sol·licituds',

                'datagrid' => [
                    'id'                  => 'Id',
                    'product-image'       => 'Imatge del Producte',
                    'mass-update-error'   => 'Error en l\'actualització de la sol·licitud!',
                    'mass-update-success' => 'Sol·licituds seleccionades actualitzades amb èxit!',
                    'product-name'        => 'Nom del Producte',
                    'outlet-name'         => 'Nom de la Botiga',
                    'qty-value'           => 'QTY - :qty',
                    'request-date'        => 'Data de la Sol·licitud',
                    'requested-qty'       => 'QTY Sol·licitada',
                    'update-status'       => 'Actualitza Estat',
                    'user-name'           => 'Nom de l\'Usuari',

                    'status' => [
                        'title' => 'Estat',

                        'options' => [
                            'complete' => 'Completat',
                            'decline'  => 'Rebutjat',
                            'pending'  => 'Pendent',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'Enrere',
                'btn-title' => 'Desa',
                'title'     => 'Detalls del Producte Sol·licitat #:id',

                'user-info' => [
                    'email'            => 'Correu electrònic',
                    'name'             => 'Nom',
                    'outlet-address'   => 'Adreça de la Botiga',
                    'outlet-inventory' => 'Font d\'Inventari de la Botiga',
                    'outlet-name'      => 'Nom de la Botiga',
                    'title'            => 'Informació de l\'Usuari',
                ],

                'request-info' => [
                    'comment'       => 'Comentari',
                    'product-name'  => 'Nom del Producte',
                    'qty-value'     => 'QTY - :qty',
                    'request-date'  => 'Data de la Sol·licitud',
                    'requested-qty' => 'QTY Sol·licitada',
                    'title'         => 'Informació de la Sol·licitud',

                    'status' => [
                        'title' => 'Estat',

                        'options' => [
                            'complete' => 'Completat',
                            'decline'  => 'Rebutjat',
                            'pending'  => 'Pendent',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'Error en l\'actualització de la sol·licitud!',
            'update-success' => 'Sol·licitud actualitzada amb èxit!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'Crear Banc',
                'title'     => 'Bancs',

                'datagrid' => [
                    'active'              => 'Actiu',
                    'address'             => 'Adreça del Banc',
                    'agent-name'          => 'Agent',
                    'delete'              => 'Eliminar',
                    'disable'             => 'Desactiva',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Bancs seleccionats eliminats amb èxit!',
                    'name'                => 'Nom del Banc',
                    'status'              => 'Estat',
                ],
            ],

            'create' => [
                'back-btn'  => 'Enrere',
                'btn-title' => 'Desa Banc',
                'title'     => 'Crear Nou Banc',

                'general' => [
                    'address' => 'Adreça',
                    'email'   => 'Correu electrònic',
                    'name'    => 'Nom',
                    'phone'   => 'Telèfon',
                    'title'   => 'General',
                ],

                'agent-and-status' => [
                    'agent'        => 'Assigna Agent POS',
                    'bank-status'  => 'Estat del Banc',
                    'select-agent' => 'Selecciona Agent',
                    'title'        => 'Agent POS i Estat del Banc',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Enrere',
                'btn-title' => 'Desa Banc',
                'title'     => 'Edita Banc',

                'general' => [
                    'address' => 'Adreça',
                    'email'   => 'Correu electrònic',
                    'name'    => 'Nom',
                    'phone'   => 'Telèfon',
                    'title'   => 'General',
                ],

                'agent-and-status' => [
                    'agent'        => 'Assigna Agent POS',
                    'bank-status'  => 'Estat del Banc',
                    'select-agent' => 'Selecciona Agent',
                    'title'        => 'Agent POS i Estat del Banc',
                ],
            ],

            'create-success' => 'Banc creat amb èxit!',
            'delete-failed'  => 'No s\'ha pogut eliminar el banc!',
            'delete-success' => 'Banc eliminat amb èxit!',
            'update-success' => 'Banc actualitzat amb èxit!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'Informes de Vendes',

                'datagrid' => [
                    'bank-name'      => 'Nom del Banc',
                    'grand-total'    => 'Total General',
                    'order-date'     => 'Data de la Comanda',
                    'order-id'       => 'ID de la Comanda',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'Nota de la Comanda',
                    'outlet-name'    => 'Nom de la Botiga',
                    'payment-method' => 'Mètode de Pagament',
                    'view'           => 'Veure',

                    'status' => [
                        'title' => 'Estat',

                        'options' => [
                            'canceled'        => 'Cancel·lat',
                            'closed'          => 'Tancat',
                            'completed'       => 'Completat',
                            'fraud'           => 'Fraudulent',
                            'pending'         => 'Pendent',
                            'pending-payment' => 'Pagament Pendent',
                            'processing'      => 'Processant',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'Crear rebut',
                'title'      => 'Rebuts',

                'datagrid' => [
                    'delete'              => 'Eliminar',
                    'edit'                => 'Editar',
                    'id'                  => 'Id',
                    'mass-delete-success' => 'Els rebuts seleccionats s’han eliminat correctament!',
                    'preview'             => 'Previsualitzar',
                    'title'               => 'Títol',

                    'status' => [
                        'title' => 'Estat',

                        'options' => [
                            'active'   => 'Actiu',
                            'inactive' => 'Inactiu',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'Enrere',
                'btn-title' => 'Desar rebut',
                'title'     => 'Crear un nou rebut',

                'general' => [
                    'cashier-name-label'      => 'Etiqueta del nom del caixer',
                    'change-amount-label'     => 'Etiqueta del canvi',
                    'credit-amount-label'     => 'Etiqueta del crèdit',
                    'discount-amt-label'      => 'Etiqueta del descompte',
                    'display-cashier-name'    => 'Mostrar el nom del caixer',
                    'display-change-amount'   => 'Mostrar el canvi',
                    'display-credit-amount'   => 'Mostrar el crèdit',
                    'display-customer-name'   => 'Mostrar el nom del client',
                    'display-date'            => 'Mostrar la data',
                    'display-discount-amt'    => 'Mostrar el descompte',
                    'display-order-id'        => 'Mostrar l’ID de comanda',
                    'display-outlet-address'  => 'Mostrar l’adreça del punt de venda',
                    'display-outlet-name'     => 'Mostrar el nom del punt de venda',
                    'display-sub-total'       => 'Mostrar el subtotal',
                    'display-tax'             => 'Mostrar els impostos',
                    'grand-total-label'       => 'Etiqueta del total final',
                    'order-id-label'          => 'Etiqueta de l’ID de comanda',
                    'receipt-title'           => 'Títol del rebut',
                    'show-order-barcode'      => 'Mostrar el codi de barres de la comanda',
                    'show-print-confirmation' => 'Mostrar la confirmació d’impressió',
                    'status'                  => 'Estat',
                    'sub-total-label'         => 'Etiqueta del subtotal',
                    'tax-label'               => 'Etiqueta dels impostos',
                    'title'                   => 'General',
                ],

                'logo' => [
                    'display-logo' => 'Mostrar el logotip',
                    'logo-alt'     => 'Text alternatiu del logotip',
                    'logo-height'  => 'Alçada del logotip (en px)',
                    'logo-width'   => 'Amplada del logotip (en px)',
                    'title'        => 'Logotip',
                    'upload-logo'  => 'Pujar logotip',
                ],

                'header' => [
                    'header-content' => 'Contingut de la capçalera',
                    'title'          => 'Capçalera',
                ],

                'footer' => [
                    'footer-content' => 'Contingut del peu de pàgina',
                    'title'          => 'Peu de pàgina',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Enrere',
                'btn-title' => 'Desar rebut',
                'title'     => 'Editar rebut',

                'general' => [
                    'cashier-name-label'      => 'Etiqueta del nom del caixer',
                    'change-amount-label'     => 'Etiqueta del canvi',
                    'credit-amount-label'     => 'Etiqueta del crèdit',
                    'discount-amt-label'      => 'Etiqueta del descompte',
                    'display-cashier-name'    => 'Mostrar el nom del caixer',
                    'display-change-amount'   => 'Mostrar el canvi',
                    'display-credit-amount'   => 'Mostrar el crèdit',
                    'display-customer-name'   => 'Mostrar el nom del client',
                    'display-date'            => 'Mostrar la data',
                    'display-discount-amt'    => 'Mostrar el descompte',
                    'display-order-id'        => 'Mostrar l’ID de comanda',
                    'display-outlet-address'  => 'Mostrar l’adreça del punt de venda',
                    'display-outlet-name'     => 'Mostrar el nom del punt de venda',
                    'display-sub-total'       => 'Mostrar el subtotal',
                    'display-tax'             => 'Mostrar els impostos',
                    'grand-total-label'       => 'Etiqueta del total final',
                    'order-id-label'          => 'Etiqueta de l’ID de comanda',
                    'receipt-title'           => 'Títol del rebut',
                    'show-order-barcode'      => 'Mostrar el codi de barres de la comanda',
                    'show-print-confirmation' => 'Mostrar la confirmació d’impressió',
                    'status'                  => 'Estat',
                    'sub-total-label'         => 'Etiqueta del subtotal',
                    'tax-label'               => 'Etiqueta dels impostos',
                    'title'                   => 'General',
                ],

                'logo' => [
                    'display-logo' => 'Mostrar el logotip',
                    'logo-alt'     => 'Text alternatiu del logotip',
                    'logo-height'  => 'Alçada del logotip (en px)',
                    'logo-width'   => 'Amplada del logotip (en px)',
                    'title'        => 'Logotip',
                    'upload-logo'  => 'Pujar logotip',
                ],

                'header' => [
                    'header-content' => 'Contingut de la capçalera',
                    'title'          => 'Capçalera',
                ],

                'footer' => [
                    'footer-content' => 'Contingut del peu de pàgina',
                    'title'          => 'Peu de pàgina',
                ],
            ],

            'preview' => [
                'amount'         => 'Import',
                'cashier'        => 'Caixer',
                'change-amount'  => 'Canvi',
                'customer'       => 'Client',
                'customer-email' => 'Correu electrònic del client',
                'customer-name'  => 'Nom del client',
                'customer-phone' => 'Telèfon del client',
                'date'           => 'Data',
                'discount'       => 'Descompte',
                'email'          => 'Correu electrònic',
                'grand-total'    => 'Total final',
                'order-id'       => 'ID de comanda',
                'phone'          => 'Telèfon',
                'price'          => 'Preu',
                'product'        => 'Producte',
                'qty'            => 'Quantitat',
                'sub-total'      => 'Subtotal',
                'tax'            => 'Impostos',
                'title'          => 'Previsualització del rebut',
                'total-qty'      => 'Quantitat total',
            ],

            'create-success' => 'Rebut creat correctament!',
            'delete-failed'  => 'Error en eliminar el rebut!',
            'delete-success' => 'Rebut eliminat correctament!',
            'update-success' => 'Rebut actualitzat correctament!',
        ],
    ],

    'emails' => [
        'dear'     => 'Benvolgut/da :name',
        'greeting' => 'Salutacions!',

        'registration' => [
            'message' => 'Enhorabona! El teu compte ha estat creat correctament. Inicia sessió per començar a utilitzar el sistema POS.',
            'subject' => 'Correu de registre d’usuari POS',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'Iniciar sessió',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
