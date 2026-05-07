<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'Credenciais inválidas.',
                'not-activated'       => 'Sua conta não está ativada.',
                'verify-first'        => 'Por favor, verifique seu e-mail primeiro.',
                'success'             => 'Você fez login com sucesso.',
            ],

            'logout' => [
                'no-login-agent' => 'Nenhum agente está logado.',
                'success'        => 'Você saiu com sucesso.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'A senha que você digitou está incorreta.',
                    'success'          => 'Sua conta foi atualizada com sucesso.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'Cliente criado com sucesso!',
            'update-success' => 'Cliente atualizado com sucesso!',
            'delete-success' => 'Cliente excluído com sucesso!',
            'delete-failed'  => 'Falha na exclusão do cliente!',
            'pending-orders' => 'O cliente tem pedidos pendentes!',
        ],

        'cart' => [
            'already-applied'     => 'Cupom já aplicado!',
            'coupon-applied'      => 'Cupom aplicado com sucesso!',
            'coupon-removed'      => 'Cupom removido com sucesso!',
            'create-success'      => 'Carrinho criado com sucesso!',
            'invalid-coupon'      => 'Código de cupom inválido!',
            'item-add-success'    => 'Produto adicionado ao carrinho com sucesso!',
            'item-remove-success' => 'Produto removido do carrinho com sucesso!',
            'item-update-success' => 'Produto atualizado com sucesso!',
            'not-found'           => 'Carrinho não encontrado!',
        ],

        'payment' => [
            'title' => 'Pagamento Pos',

            'options' => [
                'cash' => [
                    'title'       => 'Pagamento em Dinheiro Pos',
                    'description' => 'Este é um pagamento em dinheiro Pos.',
                ],

                'card' => [
                    'title'       => 'Pagamento com Cartão Pos',
                    'description' => 'Este é um pagamento com cartão Pos.',
                ],

                'split' => [
                    'title'       => 'Pagamento Dividido Pos',
                    'description' => 'Este é um pagamento dividido Pos.',
                ],
            ],

            'no-items' => 'Nenhum item no carrinho para prosseguir com o pagamento.',
            'success'  => 'Pagamento concluído com sucesso!',
        ],

        'shipping' => [
            'title'       => 'Envio Pos',
            'description' => 'Este é um envio gratuito Pos.',
        ],

        'order' => [
            'sync-success' => 'Pedido sincronizado com sucesso!',
        ],

        'return' => [
            'create-success'              => 'Reembolso criado com sucesso',
            'creation-error'              => 'A criação de reembolso não é permitida.',
            'invalid-qty'                 => 'Quantidade inválida encontrada para faturar itens.',
            'invalid-refund-amount-error' => 'O valor do reembolso deve ser maior que zero.',
            'refund-limit-error'          => 'O valor do reembolso :amount não pode ser processado.',
        ],

        'drawer' => [
            'create-success' => 'Gaveta aberta com sucesso!',
            'not-opened'     => 'A gaveta não está aberta.',
            'close-success'  => 'Gaveta fechada com sucesso!',
        ],

        'products' => [
            'request-success' => 'Pedido de produto enviado com sucesso.',
            'create-success'  => 'Produto criado com sucesso!',
        ],

        'reports' => [
            'orders'                  => 'Pedidos',
            'average-order-value'     => 'Valor Médio do Pedido',
            'average-items-per-order' => 'Média de Itens por Pedido',
            'discounted-offers'       => 'Ofertas com Desconto',
            'cash-payments'           => 'Pagamentos em Dinheiro',
            'other-payments'          => 'Outros Pagamentos',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'Nota do pedido',
                    'note-not-available' => 'Nota não disponível!',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'A Extensão do Ponto de Venda (POS) do Bagisto.',
                    'title' => 'Ponto de Venda',

                    'settings' => [
                        'info'  => 'Habilite o POS, Defina a Configuração Geral, Produto POS e Recibo.',
                        'title' => 'Configurações',

                        'general' => [
                            'footer-content'       => 'Conteúdo do Rodapé',
                            'footer-note'          => 'Nota do Rodapé',
                            'frontend-url'         => 'URL Frontend',
                            'heading-on-login'     => 'Título ao Fazer Login',
                            'info'                 => 'As configurações gerais permitem a configuração da página do usuário POS, adicionando logo, títulos, conteúdo do rodapé, nota do rodapé, etc.',
                            'pos-logo'             => 'Logo POS',
                            'status'               => 'Status',
                            'sub-heading-on-login' => 'Sub-Título ao Fazer Login',
                            'title'                => 'Geral',
                        ],

                        'barcode' => [
                            'height'             => 'Altura',
                            'hide-barcode'       => 'Ocultar Código de Barras',
                            'info'               => 'As configurações de código de barras permitem a configuração para geração de códigos de barras, altura do código de barras, largura do código de barras, tipo de código de barras, etc.',
                            'prefix'             => 'Prefixo',
                            'print-product-name' => 'Imprimir Nome do Produto',
                            'title'              => 'Código de Barras',
                            'width'              => 'Largura',

                            'generate-with' => [
                                'title' => 'Gerar Código de Barras Com',

                                'options' => [
                                    'product-id' => 'ID do Produto',
                                    'sku'        => 'SKU do Produto',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'Permitir SKU para produto personalizado',
                            'info'      => 'As configurações de produto permitem as configurações para SKU do produto.',
                            'title'     => 'Produtos',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'Atribuir Produtos',
            'banks'            => 'Bancos',
            'barcode-products' => 'Produtos com Código de Barras',
            'create'           => 'Criar',
            'delete'           => 'Excluir',
            'edit'             => 'Editar',
            'generate-barcode' => 'Gerar Código de Barras',
            'orders'           => 'Pedidos',
            'outlets'          => 'Pontos de Venda',
            'pos'              => 'Ponto de Venda (POS)',
            'preview'          => 'Visualizar',
            'print-barcode'    => 'Imprimir Código de Barras',
            'receipts'         => 'Recibos',
            'requests'         => 'Solicitações',
            'sales-report'     => 'Relatório de Vendas',
            'users'            => 'Agentes',
            'view'             => 'Visualizar',
        ],

        'layouts' => [
            'banks'            => 'Bancos',
            'barcode-products' => 'Produtos com Código de Barras',
            'orders'           => 'Pedidos',
            'pos'              => 'Ponto de Venda (POS)',
            'receipts'         => 'Recibos',
            'requests'         => 'Solicitações',
            'sales-report'     => 'Relatório de vendas',

            'users' => [
                'agents'   => 'Agentes',
                'outlets'  => 'Pontos de Venda',
                'title'    => 'Agentes',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'Criar Agentes',
                    'pos-front'  => 'Frente de POS',
                    'title'      => 'Agentes',

                    'datagrid' => [
                        'action'              => 'Ação',
                        'delete'              => 'Excluir',
                        'edit'                => 'Editar',
                        'email'               => 'E-mail',
                        'full-name'           => 'Nome completo',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'mass-delete-success' => 'Agentes selecionados excluídos com sucesso!',
                        'mass-update-success' => 'Agentes selecionados atualizados com sucesso!',
                        'outlet-name'         => 'Nome do ponto de venda',
                        'profile-image'       => 'Imagem de perfil',
                        'update-status'       => 'Atualizar status',
                        'username'            => 'Nome de usuário',

                        'status' => [
                            'title' => 'Status',

                            'options' => [
                                'active'  => 'Ativo',
                                'disable' => 'Desativado',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'Voltar',
                    'confirm-password'  => 'Confirmar Senha',
                    'email'             => 'Email',
                    'first-name'        => 'Primeiro Nome',
                    'general'           => 'Geral',
                    'image'             => 'Imagem',
                    'last-name'         => 'Último Nome',
                    'outlet'            => 'Outlet',
                    'outlet-and-status' => 'Outlet & Status',
                    'password'          => 'Senha',
                    'save-btn'          => 'Salvar Agente',
                    'select-outlet'     => 'Selecionar Outlet',
                    'status'            => 'Status',
                    'title'             => 'Adicionar Agente',
                    'user-image'        => 'Carregar Imagem do Agente',
                    'username'          => 'Nome de Usuário',
                ],

                'edit' => [
                    'back-btn'          => 'Voltar',
                    'confirm-password'  => 'Confirmar Senha',
                    'email'             => 'Email',
                    'first-name'        => 'Primeiro Nome',
                    'general'           => 'Geral',
                    'image'             => 'Imagem',
                    'last-name'         => 'Último Nome',
                    'outlet'            => 'Outlet',
                    'outlet-and-status' => 'Outlet & Status',
                    'password'          => 'Senha',
                    'save-btn'          => 'Salvar Agente',
                    'select-outlet'     => 'Selecionar Outlet',
                    'status'            => 'Status',
                    'title'             => 'Editar Agente',
                    'user-image'        => 'Carregar Imagem do Agente',
                    'username'          => 'Nome de Usuário',
                ],

                'create-success' => 'Agente criado com sucesso!',
                'delete-failed'  => 'Falha ao excluir o agente!',
                'delete-success' => 'Agente excluído com sucesso!',
                'update-success' => 'Agente atualizado com sucesso!',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'Criar Outlet',
                    'pos-front'  => 'Frente de POS',
                    'title'      => 'Outlets',

                    'datagrid' => [
                        'action'              => 'Ação',
                        'active'              => 'Ativo',
                        'assign'              => 'Atribuir Produto',
                        'delete'              => 'Excluir',
                        'edit'                => 'Editar',
                        'id'                  => 'ID',
                        'inactive'            => 'Inativo',
                        'inventory-source'    => 'Fonte de Inventário',
                        'mass-delete-success' => 'Lojas selecionadas excluídas com sucesso!',
                        'mass-update-success' => 'Lojas selecionadas atualizadas com sucesso!',
                        'name'                => 'Nome',
                        'receipt-title'       => 'Título do Recibo',
                        'status'              => 'Status',
                        'title'               => 'Lista de Lojas POS',
                        'update-status'       => 'Atualizar Status',
                    ],
                ],

                'create' => [
                    'address'                 => 'Endereço',
                    'back-btn'                => 'Voltar',
                    'btn-title'               => 'Salvar Outlet',
                    'city'                    => 'Cidade',
                    'country'                 => 'País',
                    'customer-care-number'    => 'Número de Atendimento ao Cliente',
                    'email'                   => 'Email',
                    'general'                 => 'Geral',
                    'gst-number'              => 'Número GST',
                    'inventory'               => 'Inventário',
                    'inventory-source'        => 'Fonte de Inventário',
                    'low-stock-qty'           => 'Quantidade de Estoque Baixo',
                    'name'                    => 'Nome do Outlet',
                    'phone'                   => 'Telefone',
                    'postcode'                => 'Código Postal',
                    'receipt'                 => 'Recibo',
                    'select-country'          => 'Selecionar País',
                    'select-inventory-source' => 'Selecionar Fonte de Inventário',
                    'select-receipt'          => 'Selecionar Recibo',
                    'state'                   => 'Estado',
                    'status'                  => 'Status',
                    'store-address'           => 'Endereço do Outlet',
                    'title'                   => 'Adicionar Outlet',
                    'website'                 => 'Website',
                ],

                'edit' => [
                    'address'                 => 'Endereço',
                    'back-btn'                => 'Voltar',
                    'btn-title'               => 'Salvar Outlet',
                    'city'                    => 'Cidade',
                    'country'                 => 'País',
                    'customer-care-number'    => 'Número de Atendimento ao Cliente',
                    'email'                   => 'Email',
                    'general'                 => 'Geral',
                    'gst-number'              => 'Número GST',
                    'inventory'               => 'Inventário',
                    'inventory-source'        => 'Fonte de Inventário',
                    'low-stock-qty'           => 'Quantidade de Estoque Baixo',
                    'name'                    => 'Nome do Outlet',
                    'phone'                   => 'Telefone',
                    'postcode'                => 'Código Postal',
                    'receipt'                 => 'Recibo',
                    'select-country'          => 'Selecionar País',
                    'select-inventory-source' => 'Selecionar Fonte de Inventário',
                    'select-receipt'          => 'Selecionar Recibo',
                    'state'                   => 'Estado',
                    'status'                  => 'Status',
                    'store-address'           => 'Endereço do Outlet',
                    'title'                   => 'Editar Outlet',
                    'website'                 => 'Website',
                ],

                'assign' => [
                    'back-btn' => 'Voltar',
                    'title'    => 'Gerenciar Produtos do Outlet',

                    'datagrid' => [
                        'active'              => 'Ativo',
                        'assign'              => 'Atribuir',
                        'disable'             => 'Desativar',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'image'               => 'Imagem',
                        'mass-assign-success' => 'Atribuição de produto atualizada com sucesso!',
                        'name'                => 'Nome',
                        'out-of-stock'        => 'Fora de Estoque',
                        'pos-status'          => 'Status POS',
                        'price'               => 'Preço',
                        'product-image'       => 'Imagem do Produto',
                        'qty'                 => 'Quantidade',
                        'qty-value'           => ':qty Disponível',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'Status',
                        'type'                => 'Tipo',
                        'unassign'            => 'Desatribuir',
                        'update-assign'       => 'Atualizar Atribuição',
                    ],
                ],

                'create-success' => 'Outlet criado com sucesso!',
                'delete-failed'  => 'Falha ao excluir o outlet!',
                'delete-success' => 'Outlet excluído com sucesso!',
                'update-success' => 'Outlet atualizado com sucesso!',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Produtos com Código de Barras',

                'datagrid' => [
                    'barcode'               => 'Código de Barras',
                    'generate-barcode'      => 'Gerar Código de Barras',
                    'print-barcode'         => 'Imprimir Código de Barras',
                    'id'                    => 'ID',
                    'id-value'              => 'ID - :id',
                    'image'                 => 'Imagem',
                    'mass-generate-success' => 'Códigos de barras dos produtos selecionados gerados com sucesso!',
                    'name'                  => 'Nome',
                    'out-of-stock'          => 'Fora de Estoque',
                    'price'                 => 'Preço',
                    'product-image'         => 'Imagem do Produto',
                    'qty'                   => 'Qtd',
                    'qty-value'             => ':qty Disponível',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'active'  => 'Ativo',
                            'disable' => 'Desativado',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'Voltar',
                'btn-title' => 'Imprimir',
                'qty'       => 'Qtd',
                'title'     => 'Imprimir Código de Barras',
            ],

            'generate-failed'  => 'Falha ao gerar o código de barras!',
            'generate-success' => 'Código de barras gerado com sucesso!',
        ],

        'orders' => [
            'index' => [
                'title' => 'Pedidos',

                'datagrid' => [
                    'customer-name' => 'Nome do Cliente',
                    'grand-total'   => 'Total Geral',
                    'order-date'    => 'Data do Pedido',
                    'order-id'      => 'ID do Pedido',
                    'order-ref-id'  => 'Ref. ID do Pedido',
                    'view'          => 'Visualizar',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'canceled'        => 'Cancelado',
                            'closed'          => 'Fechado',
                            'completed'       => 'Concluído',
                            'fraud'           => 'Fraude',
                            'pending'         => 'Pendente',
                            'pending-payment' => 'Aguardando Pagamento',
                            'processing'      => 'Processando',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'Solicitações',

                'datagrid' => [
                    'id'                  => 'ID',
                    'product-image'       => 'Imagem do Produto',
                    'mass-update-error'   => 'Falha ao atualizar a solicitação!',
                    'mass-update-success' => 'Solicitações selecionadas atualizadas com sucesso!',
                    'product-name'        => 'Nome do Produto',
                    'outlet-name'         => 'Nome do Ponto de Venda',
                    'qty-value'           => 'Qtd - :qty',
                    'request-date'        => 'Data da Solicitação',
                    'requested-qty'       => 'Qtd Solicitada',
                    'update-status'       => 'Atualizar Status',
                    'user-name'           => 'Nome do Usuário',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'complete' => 'Completo',
                            'decline'  => 'Recusado',
                            'pending'  => 'Pendente',
                        ],
                    ],
                ],
            ],

            'view' => [
                'btn-title' => 'Salvar',
                'title'     => 'Detalhes do Produto Solicitado #:id',

                'user-info' => [
                    'email'            => 'Email',
                    'name'             => 'Nome',
                    'outlet-address'   => 'Endereço do Ponto de Venda',
                    'outlet-inventory' => 'Fonte de Inventário do Ponto de Venda',
                    'outlet-name'      => 'Nome do Ponto de Venda',
                    'title'            => 'Informações do Usuário',
                ],

                'request-info' => [
                    'comment'       => 'Comentário',
                    'product-name'  => 'Nome do Produto',
                    'qty-value'     => 'Qtd - :qty',
                    'request-date'  => 'Data da Solicitação',
                    'requested-qty' => 'Qtd Solicitada',
                    'title'         => 'Informações da Solicitação',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'complete' => 'Completo',
                            'decline'  => 'Recusado',
                            'pending'  => 'Pendente',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'Falha ao atualizar a solicitação!',
            'update-success' => 'Solicitação atualizada com sucesso!',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'Criar Banco',
                'title'     => 'Bancos',

                'datagrid' => [
                    'active'              => 'Ativo',
                    'address'             => 'Endereço do banco',
                    'agent-name'          => 'Agente',
                    'delete'              => 'Excluir',
                    'disable'             => 'Desativar',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Bancos selecionados excluídos com sucesso!',
                    'name'                => 'Nome do banco',
                    'status'              => 'Status',
                ],
            ],

            'create' => [
                'back-btn'  => 'Voltar',
                'btn-title' => 'Salvar Banco',
                'title'     => 'Criar Novo Banco',

                'general' => [
                    'address' => 'Endereço',
                    'email'   => 'Email',
                    'name'    => 'Nome',
                    'phone'   => 'Telefone',
                    'title'   => 'Geral',
                ],

                'agent-and-status' => [
                    'agent'        => 'Atribuir Agente do POS',
                    'bank-status'  => 'Status do Banco',
                    'select-agent' => 'Selecionar Agente',
                    'title'        => 'Agente do POS & Status do Banco',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Voltar',
                'btn-title' => 'Salvar Banco',
                'title'     => 'Editar Banco',

                'general' => [
                    'address' => 'Endereço',
                    'email'   => 'Email',
                    'name'    => 'Nome',
                    'phone'   => 'Telefone',
                    'title'   => 'Geral',
                ],

                'agent-and-status' => [
                    'agent'        => 'Atribuir Agente do POS',
                    'bank-status'  => 'Status do Banco',
                    'select-agent' => 'Selecionar Agente',
                    'title'        => 'Agente do POS & Status do Banco',
                ],
            ],

            'create-success' => 'Banco criado com sucesso!',
            'delete-failed'  => 'Falha ao excluir o banco!',
            'delete-success' => 'Banco excluído com sucesso!',
            'update-success' => 'Banco atualizado com sucesso!',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'Relatório de vendas',

                'datagrid' => [
                    'bank-name'      => 'Nome do Banco',
                    'grand-total'    => 'Total Geral',
                    'order-date'     => 'Data do Pedido',
                    'order-id'       => 'ID do Pedido',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'Nota do Pedido',
                    'outlet-name'    => 'Nome do Ponto de Venda',
                    'payment-method' => 'Método de Pagamento',
                    'view'           => 'Visualizar',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'canceled'        => 'Cancelado',
                            'closed'          => 'Fechado',
                            'completed'       => 'Concluído',
                            'fraud'           => 'Fraude',
                            'pending'         => 'Pendente',
                            'pending-payment' => 'Aguardando Pagamento',
                            'processing'      => 'Processando',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'back-btn'   => 'Voltar',
                'create-btn' => 'Criar Comprovante',
                'title'      => 'Comprovantes',

                'datagrid' => [
                    'delete'              => 'Excluir',
                    'edit'                => 'Editar',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Comprovantes selecionados excluídos com sucesso!',
                    'preview'             => 'Visualizar',
                    'title'               => 'Título',

                    'status' => [
                        'title' => 'Status',

                        'options' => [
                            'active'   => 'Ativo',
                            'inactive' => 'Inativo',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'Voltar',
                'btn-title' => 'Salvar Comprovante',
                'title'     => 'Criar Novo Comprovante',

                'general' => [
                    'cashier-name-label'      => 'Rótulo do Nome do Caixa',
                    'change-amount-label'     => 'Rótulo do Valor do Troco',
                    'credit-amount-label'     => 'Rótulo do Valor do Crédito',
                    'discount-amt-label'      => 'Rótulo do Valor do Desconto',
                    'display-cashier-name'    => 'Exibir Nome do Caixa',
                    'display-change-amount'   => 'Exibir Valor do Troco',
                    'display-credit-amount'   => 'Exibir Valor do Crédito',
                    'display-customer-name'   => 'Exibir Nome do Cliente',
                    'display-date'            => 'Exibir Data',
                    'display-discount-amt'    => 'Exibir Valor do Desconto',
                    'display-order-id'        => 'Exibir ID do Pedido',
                    'display-outlet-address'  => 'Exibir Endereço do Ponto de Venda',
                    'display-outlet-name'     => 'Exibir Nome do Ponto de Venda',
                    'display-sub-total'       => 'Exibir Subtotal',
                    'display-tax'             => 'Exibir Imposto',
                    'grand-total-label'       => 'Rótulo do Total Geral',
                    'order-id-label'          => 'Rótulo do ID do Pedido',
                    'receipt-title'           => 'Título do Comprovante',
                    'show-order-barcode'      => 'Mostrar código de barras do pedido',
                    'show-print-confirmation' => 'Mostrar confirmação de impressão',
                    'status'                  => 'Status',
                    'sub-total-label'         => 'Rótulo do Subtotal',
                    'tax-label'               => 'Rótulo do Imposto',
                    'title'                   => 'Geral',
                ],

                'logo' => [
                    'display-logo' => 'Exibir Logo',
                    'logo-alt'     => 'Texto Alternativo do Logo',
                    'logo-height'  => 'Altura do Logo (em px)',
                    'logo-width'   => 'Largura do Logo (em px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Carregar Logo',
                ],

                'header' => [
                    'header-content' => 'Conteúdo do Cabeçalho',
                    'title'          => 'Cabeçalho',
                ],

                'footer' => [
                    'footer-content' => 'Conteúdo do Rodapé',
                    'title'          => 'Rodapé',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Voltar',
                'btn-title' => 'Salvar Comprovante',
                'title'     => 'Editar Comprovante',

                'general' => [
                    'cashier-name-label'      => 'Rótulo do Nome do Caixa',
                    'change-amount-label'     => 'Rótulo do Valor do Troco',
                    'credit-amount-label'     => 'Rótulo do Valor do Crédito',
                    'discount-amt-label'      => 'Rótulo do Valor do Desconto',
                    'display-cashier-name'    => 'Exibir Nome do Caixa',
                    'display-change-amount'   => 'Exibir Valor do Troco',
                    'display-credit-amount'   => 'Exibir Valor do Crédito',
                    'display-customer-name'   => 'Exibir Nome do Cliente',
                    'display-date'            => 'Exibir Data',
                    'display-discount-amt'    => 'Exibir Valor do Desconto',
                    'display-order-id'        => 'Exibir ID do Pedido',
                    'display-outlet-address'  => 'Exibir Endereço do Ponto de Venda',
                    'display-outlet-name'     => 'Exibir Nome do Ponto de Venda',
                    'display-sub-total'       => 'Exibir Subtotal',
                    'display-tax'             => 'Exibir Imposto',
                    'grand-total-label'       => 'Rótulo do Total Geral',
                    'order-id-label'          => 'Rótulo do ID do Pedido',
                    'receipt-title'           => 'Título do Comprovante',
                    'show-order-barcode'      => 'Mostrar código de barras do pedido',
                    'show-print-confirmation' => 'Mostrar confirmação de impressão',
                    'status'                  => 'Status',
                    'sub-total-label'         => 'Rótulo do Subtotal',
                    'tax-label'               => 'Rótulo do Imposto',
                    'title'                   => 'Geral',
                ],

                'logo' => [
                    'display-logo' => 'Exibir Logo',
                    'logo-alt'     => 'Texto Alternativo do Logo',
                    'logo-height'  => 'Altura do Logo (em px)',
                    'logo-width'   => 'Largura do Logo (em px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Carregar Logo',
                ],

                'header' => [
                    'header-content' => 'Conteúdo do Cabeçalho',
                    'title'          => 'Cabeçalho',
                ],

                'footer' => [
                    'footer-content' => 'Conteúdo do Rodapé',
                    'title'          => 'Rodapé',
                ],
            ],

            'preview' => [
                'amount'         => 'Valor',
                'cashier'        => 'Caixa',
                'change-amount'  => 'Troco',
                'customer'       => 'Cliente',
                'customer-email' => 'Email do Cliente',
                'customer-name'  => 'Nome do Cliente',
                'customer-phone' => 'Telefone do Cliente',
                'date'           => 'Data',
                'discount'       => 'Desconto',
                'email'          => 'Email',
                'grand-total'    => 'Total Geral',
                'order-id'       => 'ID do Pedido',
                'phone'          => 'Telefone',
                'price'          => 'Preço',
                'product'        => 'Produto',
                'qty'            => 'Qtd',
                'sub-total'      => 'Subtotal',
                'tax'            => 'Imposto',
                'title'          => 'Visualização do Comprovante',
                'total-qty'      => 'Total Qtd',
            ],

            'create-success' => 'Comprovante criado com sucesso!',
            'delete-failed'  => 'Falha ao excluir o comprovante!',
            'delete-success' => 'Comprovante excluído com sucesso!',
            'update-success' => 'Comprovante atualizado com sucesso!',
        ],
    ],

    'emails' => [
        'dear'     => 'Caro(a) :name',
        'greeting' => 'Saudações!',

        'registration' => [
            'message' => 'Parabéns! Sua conta foi criada com sucesso. Faça login na sua conta e comece a usar o sistema POS.',
            'subject' => 'Email de Registro de Usuário POS',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'Entrar',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
