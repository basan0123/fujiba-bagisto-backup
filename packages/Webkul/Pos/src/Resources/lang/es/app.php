<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'Credenciales inválidas.',
                'not-activated'       => 'Tu cuenta no está activada.',
                'verify-first'        => 'Por favor verifica tu correo electrónico primero.',
                'success'             => 'Has iniciado sesión con éxito.',
            ],

            'logout' => [
                'no-login-agent' => 'Ningún agente ha iniciado sesión.',
                'success'        => 'Has cerrado sesión con éxito.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'La contraseña que introdujiste es incorrecta.',
                    'success'          => 'Tu cuenta ha sido actualizada con éxito.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => '¡Cliente creado con éxito!',
            'update-success' => '¡Cliente actualizado con éxito!',
            'delete-success' => '¡Cliente eliminado con éxito!',
            'delete-failed'  => '¡Error al eliminar el cliente!',
            'pending-orders' => '¡El cliente tiene pedidos pendientes!',
        ],

        'cart' => [
            'already-applied'     => '¡Cupón ya aplicado!',
            'coupon-applied'      => '¡Cupón aplicado con éxito!',
            'coupon-removed'      => '¡Cupón eliminado con éxito!',
            'create-success'      => '¡Carrito creado con éxito!',
            'invalid-coupon'      => '¡Código de cupón no válido!',
            'item-add-success'    => '¡Producto añadido al carrito con éxito!',
            'item-remove-success' => '¡Producto eliminado del carrito con éxito!',
            'item-update-success' => '¡Producto actualizado con éxito!',
            'not-found'           => '¡Carrito no encontrado!',
        ],

        'payment' => [
            'title' => 'Pago Pos',

            'options' => [
                'cash' => [
                    'title'       => 'Pago en Efectivo Pos',
                    'description' => 'Este es un Pago en Efectivo Pos.',
                ],

                'card' => [
                    'title'       => 'Pago con Tarjeta Pos',
                    'description' => 'Este es un Pago con Tarjeta Pos.',
                ],

                'split' => [
                    'title'       => 'Pago Dividido Pos',
                    'description' => 'Este es un Pago Dividido Pos.',
                ],
            ],

            'no-items' => 'No hay artículos en el carrito para proceder con el pago.',
            'success'  => '¡Pago completado con éxito!',
        ],

        'shipping' => [
            'title'       => 'Envío Pos',
            'description' => 'Este es un Envío Pos Gratuito.',
        ],

        'order' => [
            'sync-success' => '¡Pedido sincronizado con éxito!',
        ],

        'return' => [
            'create-success'              => 'Reembolso creado con éxito',
            'creation-error'              => 'No se permite la creación del reembolso.',
            'invalid-qty'                 => 'Se encontró una cantidad inválida para facturar los artículos.',
            'invalid-refund-amount-error' => 'El monto del reembolso debe ser mayor que cero.',
            'refund-limit-error'          => 'No se puede procesar el monto del reembolso :amount.',
        ],

        'drawer' => [
            'create-success' => '¡Caja abierta con éxito!',
            'not-opened'     => 'La caja no está abierta.',
            'close-success'  => '¡Caja cerrada con éxito!',
        ],

        'products' => [
            'request-success' => 'Solicitud de producto enviada con éxito.',
            'create-success'  => '¡Producto creado con éxito!',
        ],

        'reports' => [
            'orders'                  => 'Pedidos',
            'average-order-value'     => 'Valor Promedio del Pedido',
            'average-items-per-order' => 'Promedio de Artículos por Pedido',
            'discounted-offers'       => 'Ofertas Descontadas',
            'cash-payments'           => 'Pagos en Efectivo',
            'other-payments'          => 'Otros Pagos',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'Nota del pedido',
                    'note-not-available' => '¡Nota no disponible!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'La Extensión de Punto de Venta (POS) de Bagisto.',
                    'title' => 'Punto de Venta',

                    'settings' => [
                        'info'  => 'Habilitar POS, Configurar Configuración General, Producto POS y Recibo de Factura.',
                        'title' => 'Configuración',

                        'general' => [
                            'footer-content'       => 'Contenido del Pie de Página',
                            'footer-note'          => 'Nota del Pie de Página',
                            'frontend-url'         => 'URL del frontend',
                            'heading-on-login'     => 'Encabezado en el Inicio de Sesión',
                            'info'                 => 'La configuración general permite las configuraciones para la página de usuario de POS, añadiendo logo, encabezados, contenidos del pie de página, nota del pie de página, etc.',
                            'pos-logo'             => 'Logo del POS',
                            'status'               => 'Estado',
                            'sub-heading-on-login' => 'Sub-Encabezado en el Inicio de Sesión',
                            'title'                => 'General',
                        ],

                        'barcode' => [
                            'height'             => 'Altura',
                            'hide-barcode'       => 'Ocultar Código de Barras',
                            'info'               => 'La configuración del código de barras permite las configuraciones para la generación de códigos de barras, altura del código de barras, ancho del código de barras, tipo de código de barras, etc.',
                            'prefix'             => 'Prefijo',
                            'print-product-name' => 'Imprimir Nombre del Producto',
                            'title'              => 'Código de Barras',
                            'width'              => 'Ancho',

                            'generate-with' => [
                                'title' => 'Generar Código de Barras Con',

                                'options' => [
                                    'product-id' => 'ID del Producto',
                                    'sku'        => 'SKU del Producto',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'Permitir SKU para producto personalizado',
                            'info'      => 'La configuración de productos permite la configuración del SKU del producto.',
                            'title'     => 'Productos',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'Asignar Productos',
            'banks'            => 'Bancos',
            'barcode-products' => 'Productos con Código de Barras',
            'create'           => 'Crear',
            'delete'           => 'Eliminar',
            'edit'             => 'Editar',
            'generate-barcode' => 'Generar Código de Barras',
            'orders'           => 'Pedidos',
            'outlets'          => 'Puntos de Venta',
            'pos'              => 'Punto de Venta (POS)',
            'preview'          => 'Vista Previa',
            'print-barcode'    => 'Imprimir Código de Barras',
            'receipts'         => 'Recibos',
            'requests'         => 'Solicitudes',
            'sales-report'     => 'Informe de Ventas',
            'users'            => 'Agentes',
            'view'             => 'Ver',
        ],

        'layouts' => [
            'banks'            => 'Bancos',
            'barcode-products' => 'Productos con Código de Barras',
            'orders'           => 'Pedidos',
            'pos'              => 'Punto de Venta (POS)',
            'receipts'         => 'Recibos',
            'requests'         => 'Solicitudes',
            'sales-report'     => 'Informe de ventas',

            'users' => [
                'agents'   => 'Agentes',
                'outlets'  => 'Puntos de Venta',
                'title'    => 'Agentes',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'Crear Agentes',
                    'pos-front'  => 'Frente de POS',
                    'title'      => 'Agentes',

                    'datagrid' => [
                        'action'              => 'Acción',
                        'delete'              => 'Eliminar',
                        'edit'                => 'Editar',
                        'email'               => 'Correo electrónico',
                        'full-name'           => 'Nombre completo',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => '¡Agentes seleccionados eliminados con éxito!',
                        'mass-update-success' => '¡Agentes seleccionados actualizados con éxito!',
                        'outlet-name'         => 'Nombre del punto de venta',
                        'profile-image'       => 'Imagen de perfil',
                        'update-status'       => 'Actualizar estado',
                        'username'            => 'Nombre de usuario',

                        'status' => [
                            'title' => 'Estado',

                            'options' => [
                                'active'  => 'Activo',
                                'disable' => 'Desactivar',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'Atrás',
                    'confirm-password'  => 'Confirmar Contraseña',
                    'email'             => 'Correo Electrónico',
                    'first-name'        => 'Nombre',
                    'general'           => 'General',
                    'image'             => 'Imagen',
                    'last-name'         => 'Apellido',
                    'outlet'            => 'Punto de Venta',
                    'outlet-and-status' => 'Punto de Venta y Estado',
                    'password'          => 'Contraseña',
                    'save-btn'          => 'Guardar Agente',
                    'select-outlet'     => 'Seleccionar Punto de Venta',
                    'status'            => 'Estado',
                    'title'             => 'Agregar Agente',
                    'user-image'        => 'Subir Imagen del Agente',
                    'username'          => 'Nombre de Usuario',
                ],

                'edit' => [
                    'back-btn'          => 'Atrás',
                    'confirm-password'  => 'Confirmar Contraseña',
                    'email'             => 'Correo Electrónico',
                    'first-name'        => 'Nombre',
                    'general'           => 'General',
                    'image'             => 'Imagen',
                    'last-name'         => 'Apellido',
                    'outlet'            => 'Punto de Venta',
                    'outlet-and-status' => 'Punto de Venta y Estado',
                    'password'          => 'Contraseña',
                    'save-btn'          => 'Guardar Agente',
                    'select-outlet'     => 'Seleccionar Punto de Venta',
                    'status'            => 'Estado',
                    'title'             => 'Editar Agente',
                    'user-image'        => 'Subir Imagen del Agente',
                    'username'          => 'Nombre de Usuario',
                ],

                'create-success' => '¡Agente creado con éxito!',
                'delete-failed'  => '¡Falló la eliminación del agente!',
                'delete-success' => '¡Agente eliminado con éxito!',
                'update-success' => '¡Agente actualizado con éxito!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'Crear Punto de Venta',
                    'pos-front'  => 'Frente de POS',
                    'title'      => 'Puntos de Venta',

                    'datagrid' => [
                        'action'              => 'Acción',
                        'active'              => 'Activo',
                        'assign'              => 'Asignar Producto',
                        'delete'              => 'Eliminar',
                        'edit'                => 'Editar',
                        'id'                  => 'Id',
                        'inactive'            => 'Inactivo',
                        'inventory-source'    => 'Fuente de Inventario',
                        'mass-delete-success' => 'نمایندگان انتخاب‌شده با موفقیت حذف شدند!',
                        'mass-update-success' => 'نمایندگان انتخاب‌شده با موفقیت به‌روزرسانی شدند!',
                        'name'                => 'Nombre',
                        'receipt-title'       => 'Título del Recibo',
                        'status'              => 'Estado',
                        'title'               => 'Lista de Tiendas POS',
                        'update-status'       => 'Actualizar Estado',
                    ],
                ],

                'create' => [
                    'address'                 => 'Dirección',
                    'back-btn'                => 'Atrás',
                    'btn-title'               => 'Guardar Sucursal',
                    'city'                    => 'Ciudad',
                    'country'                 => 'País',
                    'customer-care-number'    => 'Número de Atención al Cliente',
                    'email'                   => 'Correo Electrónico',
                    'general'                 => 'General',
                    'gst-number'              => 'Número GST',
                    'inventory'               => 'Inventario',
                    'inventory-source'        => 'Fuente de Inventario',
                    'low-stock-qty'           => 'Cantidad Baja de Inventario',
                    'name'                    => 'Nombre de la Sucursal',
                    'phone'                   => 'Teléfono',
                    'postcode'                => 'Código Postal',
                    'receipt'                 => 'Recibo',
                    'select-country'          => 'Seleccionar País',
                    'select-inventory-source' => 'Seleccionar Fuente de Inventario',
                    'select-receipt'          => 'Seleccionar Recibo',
                    'state'                   => 'Estado',
                    'status'                  => 'Estado',
                    'store-address'           => 'Dirección de la Sucursal',
                    'title'                   => 'Agregar Sucursal',
                    'website'                 => 'Sitio Web',
                ],

                'edit' => [
                    'address'                 => 'Dirección',
                    'back-btn'                => 'Atrás',
                    'btn-title'               => 'Guardar Sucursal',
                    'city'                    => 'Ciudad',
                    'country'                 => 'País',
                    'customer-care-number'    => 'Número de Atención al Cliente',
                    'email'                   => 'Correo Electrónico',
                    'general'                 => 'General',
                    'gst-number'              => 'Número GST',
                    'inventory'               => 'Inventario',
                    'inventory-source'        => 'Fuente de Inventario',
                    'low-stock-qty'           => 'Cantidad Baja de Inventario',
                    'name'                    => 'Nombre de la Sucursal',
                    'phone'                   => 'Teléfono',
                    'postcode'                => 'Código Postal',
                    'receipt'                 => 'Recibo',
                    'select-country'          => 'Seleccionar País',
                    'select-inventory-source' => 'Seleccionar Fuente de Inventario',
                    'select-receipt'          => 'Seleccionar Recibo',
                    'state'                   => 'Estado',
                    'status'                  => 'Estado',
                    'store-address'           => 'Dirección de la Sucursal',
                    'title'                   => 'Editar Sucursal',
                    'website'                 => 'Sitio Web',
                ],

                'assign' => [
                    'back-btn' => 'Atrás',
                    'title'    => 'Gestionar Productos del Punto de Venta',

                    'datagrid' => [
                        'active'              => 'Activo',
                        'assign'              => 'Asignar',
                        'disable'             => 'Desactivar',
                        'id'                  => 'Id',
                        'id-value'            => 'Id - :id',
                        'image'               => 'Imagen',
                        'mass-assign-success' => '¡Asignación de producto actualizada con éxito!',
                        'name'                => 'Nombre',
                        'out-of-stock'        => 'Agotado',
                        'pos-status'          => 'Estado POS',
                        'price'               => 'Precio',
                        'product-image'       => 'Imagen del Producto',
                        'qty'                 => 'Cantidad',
                        'qty-value'           => ':qty Disponible',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'Estado',
                        'type'                => 'Tipo',
                        'unassign'            => 'Desasignar',
                        'update-assign'       => 'Actualizar Asignación',
                    ],
                ],

                'create-success' => '¡Punto de venta creado con éxito!',
                'delete-failed'  => '¡Falló la eliminación del punto de venta!',
                'delete-success' => '¡Punto de venta eliminado con éxito!',
                'update-success' => '¡Punto de venta actualizado con éxito!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Productos con Código de Barras',

                'datagrid' => [
                    'barcode'               => 'Código de barras',
                    'generate-barcode'      => 'Generar código de barras',
                    'print-barcode'         => 'Imprimir código de barras',
                    'id'                    => 'Id',
                    'id-value'              => 'Id - :id',
                    'image'                 => 'Imagen',
                    'mass-generate-success' => '¡Códigos de barras de los productos seleccionados generados exitosamente!',
                    'name'                  => 'Nombre',
                    'out-of-stock'          => 'Agotado',
                    'price'                 => 'Precio',
                    'product-image'         => 'Imagen del producto',
                    'qty'                   => 'Cantidad',
                    'qty-value'             => ':qty Disponible',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'Estado',

                        'options' => [
                            'active'  => 'Activo',
                            'disable' => 'Desactivar',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'Atrás',
                'btn-title' => 'Imprimir',
                'qty'       => 'Cantidad',
                'title'     => 'Imprimir código de barras',
            ],

            'generate-failed'  => '¡La generación del código de barras falló!',
            'generate-success' => '¡Código de barras generado exitosamente!',
        ],

        'orders' => [
            'index' => [
                'title' => 'Pedidos',

                'datagrid' => [
                    'customer-name' => 'Nombre del Cliente',
                    'grand-total'   => 'Total General',
                    'order-date'    => 'Fecha del Pedido',
                    'order-id'      => 'ID del Pedido',
                    'order-ref-id'  => 'ID de Referencia del Pedido',
                    'view'          => 'Ver',

                    'status' => [
                        'title' => 'Estado',

                        'options' => [
                            'canceled'        => 'Cancelado',
                            'closed'          => 'Cerrado',
                            'completed'       => 'Completado',
                            'fraud'           => 'Fraude',
                            'pending'         => 'Pendiente',
                            'pending-payment' => 'Pendiente de Pago',
                            'processing'      => 'En Proceso',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'Solicitudes',

                'datagrid' => [
                    'id'                  => 'Id',
                    'product-image'       => 'Imagen del Producto',
                    'mass-update-error'   => '¡La actualización de la solicitud falló!',
                    'mass-update-success' => '¡Solicitudes seleccionadas actualizadas exitosamente!',
                    'product-name'        => 'Nombre del Producto',
                    'outlet-name'         => 'Nombre del Establecimiento',
                    'qty-value'           => 'Cantidad - :qty',
                    'request-date'        => 'Fecha de Solicitud',
                    'requested-qty'       => 'Cantidad Solicitada',
                    'update-status'       => 'Actualizar Estado',
                    'user-name'           => 'Nombre del Usuario',

                    'status' => [
                        'title' => 'Estado',

                        'options' => [
                            'complete' => 'Completa',
                            'decline'  => 'Rechazada',
                            'pending'  => 'Pendiente',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'Atrás',
                'btn-title' => 'Guardar',
                'title'     => 'Detalles del Producto Solicitado #:id',

                'user-info' => [
                    'email'            => 'Correo Electrónico',
                    'name'             => 'Nombre',
                    'outlet-address'   => 'Dirección del Establecimiento',
                    'outlet-inventory' => 'Fuente de Inventario del Establecimiento',
                    'outlet-name'      => 'Nombre del Establecimiento',
                    'title'            => 'Información del Usuario',
                ],

                'request-info' => [
                    'comment'       => 'Comentario',
                    'product-name'  => 'Nombre del Producto',
                    'qty-value'     => 'Cantidad - :qty',
                    'request-date'  => 'Fecha de Solicitud',
                    'requested-qty' => 'Cantidad Solicitada',
                    'title'         => 'Información de la Solicitud',

                    'status' => [
                        'title' => 'Estado',

                        'options' => [
                            'complete' => 'Completa',
                            'decline'  => 'Rechazada',
                            'pending'  => 'Pendiente',
                        ],
                    ],
                ],
            ],

            'update-failed'  => '¡La actualización de la solicitud falló!',
            'update-success' => '¡Solicitud actualizada exitosamente!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'Crear Banco',
                'title'     => 'Bancos',

                'datagrid' => [
                    'active'              => 'Activo',
                    'address'             => 'Dirección del banco',
                    'agent-name'          => 'Agente',
                    'delete'              => 'Eliminar',
                    'disable'             => 'Desactivar',
                    'id'                  => 'ID',
                    'mass-delete-success' => '¡Bancos seleccionados eliminados con éxito!',
                    'name'                => 'Nombre del banco',
                    'status'              => 'Estado',
                ],
            ],

            'create' => [
                'back-btn'  => 'Atrás',
                'btn-title' => 'Guardar Banco',
                'title'     => 'Crear Nuevo Banco',

                'general' => [
                    'address' => 'Dirección',
                    'email'   => 'Correo Electrónico',
                    'name'    => 'Nombre',
                    'phone'   => 'Teléfono',
                    'title'   => 'General',
                ],

                'agent-and-status' => [
                    'agent'        => 'Asignar Agente de Pos',
                    'bank-status'  => 'Estado del Banco',
                    'select-agent' => 'Seleccionar Agente',
                    'title'        => 'Agente de Pos & Estado del Banco',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Atrás',
                'btn-title' => 'Guardar Banco',
                'title'     => 'Editar Banco',

                'general' => [
                    'address' => 'Dirección',
                    'email'   => 'Correo Electrónico',
                    'name'    => 'Nombre',
                    'phone'   => 'Teléfono',
                    'title'   => 'General',
                ],

                'agent-and-status' => [
                    'agent'        => 'Asignar Agente de Pos',
                    'bank-status'  => 'Estado del Banco',
                    'select-agent' => 'Seleccionar Agente',
                    'title'        => 'Agente de Pos & Estado del Banco',
                ],
            ],

            'create-success' => '¡Banco creado exitosamente!',
            'delete-failed'  => '¡La eliminación del banco falló!',
            'delete-success' => '¡Banco eliminado exitosamente!',
            'update-success' => '¡Banco actualizado exitosamente!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'Informe de ventas',

                'datagrid' => [
                    'bank-name'      => 'Nombre del Banco',
                    'grand-total'    => 'Total General',
                    'order-date'     => 'Fecha del Pedido',
                    'order-id'       => 'ID del Pedido',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'Nota del Pedido',
                    'outlet-name'    => 'Nombre del Establecimiento',
                    'payment-method' => 'Método de Pago',
                    'view'           => 'Ver',

                    'status' => [
                        'title' => 'Estado',

                        'options' => [
                            'canceled'        => 'Cancelado',
                            'closed'          => 'Cerrado',
                            'completed'       => 'Completado',
                            'fraud'           => 'Fraude',
                            'pending'         => 'Pendiente',
                            'pending-payment' => 'Pendiente de Pago',
                            'processing'      => 'En Proceso',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'Crear Recibo',
                'title'      => 'Recibos',

                'datagrid' => [
                    'delete'              => 'Eliminar',
                    'edit'                => 'Editar',
                    'id'                  => 'Id',
                    'mass-delete-success' => '¡Recibos seleccionados eliminados exitosamente!',
                    'preview'             => 'Previsualizar',
                    'title'               => 'Título',

                    'status' => [
                        'title' => 'Estado',

                        'options' => [
                            'active'   => 'Activo',
                            'inactive' => 'Inactivo',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'Atrás',
                'btn-title' => 'Guardar Recibo',
                'title'     => 'Crear Nuevo Recibo',

                'general' => [
                    'cashier-name-label'      => 'Etiqueta del Nombre del Cajero',
                    'change-amount-label'     => 'Etiqueta del Monto de Cambio',
                    'credit-amount-label'     => 'Etiqueta del Monto de Crédito',
                    'discount-amt-label'      => 'Etiqueta del Monto de Descuento',
                    'display-cashier-name'    => 'Mostrar Nombre del Cajero',
                    'display-change-amount'   => 'Mostrar Monto de Cambio',
                    'display-credit-amount'   => 'Mostrar Monto de Crédito',
                    'display-customer-name'   => 'Mostrar Nombre del Cliente',
                    'display-date'            => 'Mostrar Fecha',
                    'display-discount-amt'    => 'Mostrar Monto de Descuento',
                    'display-order-id'        => 'Mostrar ID del Pedido',
                    'display-outlet-address'  => 'Mostrar Dirección del Establecimiento',
                    'display-outlet-name'     => 'Mostrar Nombre del Establecimiento',
                    'display-sub-total'       => 'Mostrar Subtotal',
                    'display-tax'             => 'Mostrar Impuesto',
                    'grand-total-label'       => 'Etiqueta del Total General',
                    'order-id-label'          => 'Etiqueta del ID del Pedido',
                    'receipt-title'           => 'Título del Recibo',
                    'show-order-barcode'      => 'Mostrar código de barras del pedido',
                    'show-print-confirmation' => 'Mostrar confirmación de impresión',
                    'status'                  => 'Estado',
                    'sub-total-label'         => 'Etiqueta del Subtotal',
                    'tax-label'               => 'Etiqueta del Impuesto',
                    'title'                   => 'General',
                ],

                'logo' => [
                    'display-logo' => 'Mostrar Logo',
                    'logo-alt'     => 'Texto Alternativo del Logo',
                    'logo-height'  => 'Altura del Logo (en px)',
                    'logo-width'   => 'Ancho del Logo (en px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Subir Logo',
                ],

                'header' => [
                    'header-content' => 'Contenido del Encabezado',
                    'title'          => 'Encabezado',
                ],

                'footer' => [
                    'footer-content' => 'Contenido del Pie de Página',
                    'title'          => 'Pie de Página',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Atrás',
                'btn-title' => 'Guardar Recibo',
                'title'     => 'Editar Recibo',

                'general' => [
                    'cashier-name-label'      => 'Etiqueta del Nombre del Cajero',
                    'change-amount-label'     => 'Etiqueta del Monto de Cambio',
                    'credit-amount-label'     => 'Etiqueta del Monto de Crédito',
                    'discount-amt-label'      => 'Etiqueta del Monto de Descuento',
                    'display-cashier-name'    => 'Mostrar Nombre del Cajero',
                    'display-change-amount'   => 'Mostrar Monto de Cambio',
                    'display-credit-amount'   => 'Mostrar Monto de Crédito',
                    'display-customer-name'   => 'Mostrar Nombre del Cliente',
                    'display-date'            => 'Mostrar Fecha',
                    'display-discount-amt'    => 'Mostrar Monto de Descuento',
                    'display-order-id'        => 'Mostrar ID del Pedido',
                    'display-outlet-address'  => 'Mostrar Dirección del Establecimiento',
                    'display-outlet-name'     => 'Mostrar Nombre del Establecimiento',
                    'display-sub-total'       => 'Mostrar Subtotal',
                    'display-tax'             => 'Mostrar Impuesto',
                    'grand-total-label'       => 'Etiqueta del Total General',
                    'order-id-label'          => 'Etiqueta del ID del Pedido',
                    'receipt-title'           => 'Título del Recibo',
                    'show-order-barcode'      => 'Mostrar código de barras del pedido',
                    'show-print-confirmation' => 'Mostrar confirmación de impresión',
                    'status'                  => 'Estado',
                    'sub-total-label'         => 'Etiqueta del Subtotal',
                    'tax-label'               => 'Etiqueta del Impuesto',
                    'title'                   => 'General',
                ],

                'logo' => [
                    'display-logo' => 'Mostrar Logo',
                    'logo-alt'     => 'Texto Alternativo del Logo',
                    'logo-height'  => 'Altura del Logo (en px)',
                    'logo-width'   => 'Ancho del Logo (en px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Subir Logo',
                ],

                'header' => [
                    'header-content' => 'Contenido del Encabezado',
                    'title'          => 'Encabezado',
                ],

                'footer' => [
                    'footer-content' => 'Contenido del Pie de Página',
                    'title'          => 'Pie de Página',
                ],
            ],

            'preview' => [
                'amount'         => 'Monto',
                'cashier'        => 'Cajero',
                'change-amount'  => 'Cambio',
                'customer'       => 'Cliente',
                'customer-email' => 'Correo Electrónico del Cliente',
                'customer-name'  => 'Nombre del Cliente',
                'customer-phone' => 'Teléfono del Cliente',
                'date'           => 'Fecha',
                'discount'       => 'Descuento',
                'email'          => 'Correo Electrónico',
                'grand-total'    => 'Total General',
                'order-id'       => 'ID del Pedido',
                'phone'          => 'Teléfono',
                'price'          => 'Precio',
                'product'        => 'Producto',
                'qty'            => 'Cantidad',
                'sub-total'      => 'Subtotal',
                'tax'            => 'Impuesto',
                'title'          => 'Previsualización del Recibo',
                'total-qty'      => 'Cantidad Total',
            ],

            'create-success' => '¡Recibo creado exitosamente!',
            'delete-failed'  => '¡La eliminación del recibo falló!',
            'delete-success' => '¡Recibo eliminado exitosamente!',
            'update-success' => '¡Recibo actualizado exitosamente!',
        ],
    ],

    'emails' => [
        'dear'     => 'Estimado/a :name',
        'greeting' => '¡Saludos!',

        'registration' => [
            'message' => '¡Felicidades! Tu cuenta ha sido creada exitosamente. Por favor, inicia sesión en tu cuenta y comienza a usar el sistema POS.',
            'subject' => 'Correo de Registro de Usuario POS',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'Iniciar sesión',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
