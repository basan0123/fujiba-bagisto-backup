<?php

return [
    'outlet' => [
        'agents' => [
            'login' => [
                'invalid-credentials' => 'Identifiants invalides.',
                'not-activated'       => 'Votre compte n\'est pas activé.',
                'verify-first'        => 'Veuillez vérifier d\'abord votre email.',
                'success'             => 'Vous vous êtes connecté avec succès.',
            ],

            'logout' => [
                'no-login-agent' => 'Aucun agent n\'est connecté.',
                'success'        => 'Vous vous êtes déconnecté avec succès.',
            ],

            'account' => [
                'update' => [
                    'invalid-password' => 'Le mot de passe que vous avez entré est incorrect.',
                    'success'          => 'Votre compte a été mis à jour avec succès.',
                ],
            ],
        ],

        'customers' => [
            'create-success' => 'Client créé avec succès!',
            'update-success' => 'Client mis à jour avec succès!',
            'delete-success' => 'Client supprimé avec succès!',
            'delete-failed'  => 'Échec de la suppression du client!',
            'pending-orders' => 'Le client a des commandes en attente!',
        ],

        'cart' => [
            'already-applied'     => 'Coupon déjà appliqué!',
            'coupon-applied'      => 'Coupon appliqué avec succès!',
            'coupon-removed'      => 'Coupon supprimé avec succès!',
            'create-success'      => 'Panier créé avec succès!',
            'invalid-coupon'      => 'Code de coupon invalide!',
            'item-add-success'    => 'Produit ajouté au panier avec succès!',
            'item-remove-success' => 'Produit retiré du panier avec succès!',
            'item-update-success' => 'Produit mis à jour avec succès!',
            'not-found'           => 'Panier non trouvé!',
        ],

        'payment' => [
            'title' => 'Paiement Pos',

            'options' => [
                'cash' => [
                    'title'       => 'Paiement en espèces Pos',
                    'description' => 'Ceci est un paiement en espèces Pos.',
                ],

                'card' => [
                    'title'       => 'Paiement par carte Pos',
                    'description' => 'Ceci est un paiement par carte Pos.',
                ],

                'split' => [
                    'title'       => 'Paiement divisé Pos',
                    'description' => 'Ceci est un paiement divisé Pos.',
                ],
            ],

            'no-items' => 'Aucun article dans le panier pour procéder au paiement.',
            'success'  => 'Paiement effectué avec succès !',
        ],

        'shipping' => [
            'title'       => 'Expédition Pos',
            'description' => 'Ceci est une expédition gratuite Pos.',
        ],

        'order' => [
            'sync-success' => 'Commande synchronisée avec succès!',
        ],

        'return' => [
            'create-success'              => 'Remboursement créé avec succès',
            'creation-error'              => 'La création du remboursement n’est pas autorisée.',
            'invalid-qty'                 => 'Quantité invalide trouvée pour facturer les articles.',
            'invalid-refund-amount-error' => 'Le montant du remboursement doit être supérieur à zéro.',
            'refund-limit-error'          => 'Le montant du remboursement :amount ne peut pas être traité.',
        ],

        'drawer' => [
            'create-success' => 'Tiroir ouvert avec succès !',
            'not-opened'     => 'Le tiroir n\'est pas ouvert.',
            'close-success'  => 'Tiroir fermé avec succès !',
        ],

        'products' => [
            'request-success' => 'Demande de produit envoyée avec succès.',
            'create-success'  => 'Produit créé avec succès !',
        ],

        'reports' => [
            'orders'                  => 'Commandes',
            'average-order-value'     => 'Valeur moyenne des commandes',
            'average-items-per-order' => 'Nombre moyen d\'articles par commande',
            'discounted-offers'       => 'Offres avec réduction',
            'cash-payments'           => 'Paiements en espèces',
            'other-payments'          => 'Autres paiements',
        ],
    ],

    'admin' => [
        'sales' => [
            'orders' => [
                'view' => [
                    'order-note'         => 'Note de commande',
                    'note-not-available' => 'Note non disponible !',
                ],
            ],
        ],

        'configuration' => [
            'index' => [
                'pos' => [
                    'info'  => 'L\'Extension Point de Vente (POS) de Bagisto.',
                    'title' => 'Point de Vente',

                    'settings' => [
                        'info'  => 'Activer le POS, Configurer la Configuration Générale, Produit POS et Reçu de Facture.',
                        'title' => 'Paramètres',

                        'general' => [
                            'footer-content'       => 'Contenu du Pied de Page',
                            'footer-note'          => 'Note du Pied de Page',
                            'frontend-url'         => 'URL du frontend',
                            'heading-on-login'     => 'Titre à la Connexion',
                            'info'                 => 'Les paramètres généraux permettent de configurer la page utilisateur POS, en ajoutant un logo, des titres, des contenus de pied de page, des notes de pied de page, etc.',
                            'pos-logo'             => 'Logo du POS',
                            'status'               => 'Statut',
                            'sub-heading-on-login' => 'Sous-Titre à la Connexion',
                            'title'                => 'Général',
                        ],

                        'barcode' => [
                            'height'             => 'Hauteur',
                            'hide-barcode'       => 'Masquer le Code à Barres',
                            'info'               => 'Les paramètres du code à barres permettent de configurer la génération de codes à barres, la hauteur du code à barres, la largeur du code à barres, le type de code à barres, etc.',
                            'prefix'             => 'Préfixe',
                            'print-product-name' => 'Imprimer le Nom du Produit',
                            'title'              => 'Code à Barres',
                            'width'              => 'Largeur',

                            'generate-with' => [
                                'title' => 'Générer le Code à Barres Avec',

                                'options' => [
                                    'product-id' => 'ID du Produit',
                                    'sku'        => 'SKU du Produit',
                                ],
                            ],
                        ],

                        'products' => [
                            'allow-sku' => 'Autoriser SKU pour produit personnalisé',
                            'info'      => 'Les paramètres de produit permettent les configurations pour le SKU du produit.',
                            'title'     => 'Produits',
                        ],
                    ],
                ],
            ],
        ],

        'acl' => [
            'assign-products'  => 'Attribuer des produits',
            'banks'            => 'Banques',
            'barcode-products' => 'Produits avec code-barres',
            'create'           => 'Créer',
            'delete'           => 'Supprimer',
            'edit'             => 'Modifier',
            'generate-barcode' => 'Générer un code-barres',
            'orders'           => 'Commandes',
            'outlets'          => 'Points de vente',
            'pos'              => 'Point de vente (POS)',
            'preview'          => 'Aperçu',
            'print-barcode'    => 'Imprimer le code-barres',
            'receipts'         => 'Reçus',
            'requests'         => 'Demandes',
            'sales-report'     => 'Rapport de ventes',
            'users'            => 'Agents',
            'view'             => 'Voir',
        ],

        'layouts' => [
            'banks'            => 'Banques',
            'barcode-products' => 'Produits avec Code-barres',
            'orders'           => 'Commandes',
            'pos'              => 'Point de Vente (POS)',
            'receipts'         => 'Reçus',
            'requests'         => 'Demandes',
            'sales-report'     => 'Rapport de ventes',

            'users' => [
                'agents'   => 'Agents',
                'outlets'  => 'Points de Vente',
                'title'    => 'Agents',
            ],
        ],

        'users' => [
            'users' => [
                'index' => [
                    'create-btn' => 'Créer des Agents',
                    'pos-front'  => 'Front POS',
                    'title'      => 'Agents',

                    'datagrid' => [
                        'action'              => 'Action',
                        'delete'              => 'Supprimer',
                        'edit'                => 'Éditer',
                        'email'               => 'E-mail',
                        'full-name'           => 'Nom complet',
                        'id'                  => 'Identifiant',
                        'id-value'            => 'Identifiant - :id',
                        'mass-delete-success' => 'Agents sélectionnés supprimés avec succès!',
                        'mass-update-success' => 'Agents sélectionnés mis à jour avec succès!',
                        'outlet-name'         => 'Nom du point de vente',
                        'profile-image'       => 'Image de profil',
                        'update-status'       => 'Mettre à jour le statut',
                        'username'            => 'Nom d\'utilisateur',

                        'status' => [
                            'title' => 'Statut',

                            'options' => [
                                'active'  => 'Actif',
                                'disable' => 'Désactivé',
                            ],
                        ],
                    ],
                ],

                'create' => [
                    'back-btn'          => 'Retour',
                    'confirm-password'  => 'Confirmer le Mot de Passe',
                    'email'             => 'Email',
                    'first-name'        => 'Prénom',
                    'general'           => 'Général',
                    'image'             => 'Image',
                    'last-name'         => 'Nom de Famille',
                    'outlet'            => 'Point de Vente',
                    'outlet-and-status' => 'Point de Vente & Statut',
                    'password'          => 'Mot de Passe',
                    'save-btn'          => 'Sauvegarder l\'Agent',
                    'select-outlet'     => 'Sélectionner un Point de Vente',
                    'status'            => 'Statut',
                    'title'             => 'Ajouter un Agent',
                    'user-image'        => 'Télécharger l\'Image de l\'Agent',
                    'username'          => 'Nom d\'Utilisateur',
                ],

                'edit' => [
                    'back-btn'          => 'Retour',
                    'confirm-password'  => 'Confirmer le Mot de Passe',
                    'email'             => 'Email',
                    'first-name'        => 'Prénom',
                    'general'           => 'Général',
                    'image'             => 'Image',
                    'last-name'         => 'Nom de Famille',
                    'outlet'            => 'Point de Vente',
                    'outlet-and-status' => 'Point de Vente & Statut',
                    'password'          => 'Mot de Passe',
                    'save-btn'          => 'Sauvegarder l\'Agent',
                    'select-outlet'     => 'Sélectionner un Point de Vente',
                    'status'            => 'Statut',
                    'title'             => 'Éditer l\'Agent',
                    'user-image'        => 'Télécharger l\'Image de l\'Agent',
                    'username'          => 'Nom d\'Utilisateur',
                ],

                'create-success' => 'Agent créé avec succès !',
                'delete-failed'  => 'Échec de la suppression de l\'agent !',
                'delete-success' => 'Agent supprimé avec succès !',
                'update-success' => 'Agent mis à jour avec succès !',
            ],

            'outlets' => [
                'index' => [
                    'create-btn' => 'Créer un Point de Vente',
                    'pos-front'  => 'Front POS',
                    'title'      => 'Points de Vente',

                    'datagrid' => [
                        'action'              => 'Action',
                        'active'              => 'Actif',
                        'assign'              => 'Attribuer le Produit',
                        'delete'              => 'Supprimer',
                        'edit'                => 'Éditer',
                        'id'                  => 'ID',
                        'inactive'            => 'Inactif',
                        'inventory-source'    => 'Source d\'Inventaire',
                        'mass-delete-success' => 'Les magasins sélectionnés ont été supprimés avec succès !',
                        'mass-update-success' => 'Les magasins sélectionnés ont été mis à jour avec succès !',
                        'name'                => 'Nom',
                        'receipt-title'       => 'Titre du Reçu',
                        'status'              => 'Statut',
                        'title'               => 'Liste des Magasins POS',
                        'update-status'       => 'Mettre à jour le Statut',
                    ],
                ],

                'create' => [
                    'address'                 => 'Adresse',
                    'back-btn'                => 'Retour',
                    'btn-title'               => 'Enregistrer le point de vente',
                    'city'                    => 'Ville',
                    'country'                 => 'Pays',
                    'customer-care-number'    => 'Numéro du service client',
                    'email'                   => 'E-mail',
                    'general'                 => 'Général',
                    'gst-number'              => 'Numéro de GST',
                    'inventory'               => 'Inventaire',
                    'inventory-source'        => 'Source d\'inventaire',
                    'low-stock-qty'           => 'Quantité faible de stock',
                    'name'                    => 'Nom du point de vente',
                    'phone'                   => 'Téléphone',
                    'postcode'                => 'Code postal',
                    'receipt'                 => 'Reçu',
                    'select-country'          => 'Sélectionner un pays',
                    'select-inventory-source' => 'Sélectionner une source d\'inventaire',
                    'select-receipt'          => 'Sélectionner un reçu',
                    'state'                   => 'État',
                    'status'                  => 'Statut',
                    'store-address'           => 'Adresse du point de vente',
                    'title'                   => 'Ajouter un point de vente',
                    'website'                 => 'Site web',
                ],

                'edit' => [
                    'address'                 => 'Adresse',
                    'back-btn'                => 'Retour',
                    'btn-title'               => 'Enregistrer le point de vente',
                    'city'                    => 'Ville',
                    'country'                 => 'Pays',
                    'customer-care-number'    => 'Numéro du service client',
                    'email'                   => 'E-mail',
                    'general'                 => 'Général',
                    'gst-number'              => 'Numéro de GST',
                    'inventory'               => 'Inventaire',
                    'inventory-source'        => 'Source d\'inventaire',
                    'low-stock-qty'           => 'Quantité faible de stock',
                    'name'                    => 'Nom du point de vente',
                    'phone'                   => 'Téléphone',
                    'postcode'                => 'Code postal',
                    'receipt'                 => 'Reçu',
                    'select-country'          => 'Sélectionner un pays',
                    'select-inventory-source' => 'Sélectionner une source d\'inventaire',
                    'select-receipt'          => 'Sélectionner un reçu',
                    'state'                   => 'État',
                    'status'                  => 'Statut',
                    'store-address'           => 'Adresse du point de vente',
                    'title'                   => 'Modifier le point de vente',
                    'website'                 => 'Site web',
                ],

                'assign' => [
                    'back-btn' => 'Retour',
                    'title'    => 'Gérer les Produits du Point de Vente',

                    'datagrid' => [
                        'active'              => 'Actif',
                        'assign'              => 'Attribuer',
                        'disable'             => 'Désactiver',
                        'id'                  => 'ID',
                        'id-value'            => 'ID - :id',
                        'image'               => 'Image',
                        'mass-assign-success' => 'L\'attribution des produits a été mise à jour avec succès !',
                        'name'                => 'Nom',
                        'out-of-stock'        => 'Rupture de Stock',
                        'pos-status'          => 'Statut POS',
                        'price'               => 'Prix',
                        'product-image'       => 'Image du Produit',
                        'qty'                 => 'Quantité',
                        'qty-value'           => ':qty Disponible',
                        'sku'                 => 'SKU',
                        'sku-value'           => 'SKU - :sku',
                        'status'              => 'Statut',
                        'type'                => 'Type',
                        'unassign'            => 'Désattribuer',
                        'update-assign'       => 'Mettre à Jour l\'Attribution',
                    ],
                ],

                'create-success' => 'Point de Vente créé avec succès !',
                'delete-failed'  => 'Échec de la suppression du Point de Vente !',
                'delete-success' => 'Point de Vente supprimé avec succès !',
                'update-success' => 'Point de Vente mis à jour avec succès !',
            ],
        ],

        'barcode-products' => [
            'index' => [
                'title' => 'Produits avec Code-barres',

                'datagrid' => [
                    'barcode'               => 'Code-barres',
                    'generate-barcode'      => 'Générer le Code-barres',
                    'print-barcode'         => 'Imprimer le Code-barres',
                    'id'                    => 'ID',
                    'id-value'              => 'ID - :id',
                    'image'                 => 'Image',
                    'mass-generate-success' => 'Les codes-barres des produits sélectionnés ont été générés avec succès !',
                    'name'                  => 'Nom',
                    'out-of-stock'          => 'Rupture de Stock',
                    'price'                 => 'Prix',
                    'product-image'         => 'Image du Produit',
                    'qty'                   => 'Quantité',
                    'qty-value'             => ':qty Disponible',
                    'sku'                   => 'SKU',
                    'sku-value'             => 'SKU - :sku',

                    'status' => [
                        'title' => 'Statut',

                        'options' => [
                            'active'  => 'Actif',
                            'disable' => 'Désactiver',
                        ],
                    ],
                ],
            ],

            'print' => [
                'back-btn'  => 'Retour',
                'btn-title' => 'Imprimer',
                'qty'       => 'Quantité',
                'title'     => 'Imprimer le Code-barres',
            ],

            'generate-failed'  => 'Échec de la génération du code-barres !',
            'generate-success' => 'Code-barres généré avec succès !',
        ],

        'orders' => [
            'index' => [
                'title' => 'Commandes',

                'datagrid' => [
                    'customer-name' => 'Nom du Client',
                    'grand-total'   => 'Total Général',
                    'order-date'    => 'Date de Commande',
                    'order-id'      => 'ID de Commande',
                    'order-ref-id'  => 'ID de Référence de Commande',
                    'view'          => 'Voir',

                    'status' => [
                        'title' => 'Statut',

                        'options' => [
                            'canceled'        => 'Annulé',
                            'closed'          => 'Fermé',
                            'completed'       => 'Complété',
                            'fraud'           => 'Fraude',
                            'pending'         => 'En Attente',
                            'pending-payment' => 'Paiement en Attente',
                            'processing'      => 'En Cours',
                        ],
                    ],
                ],
            ],
        ],

        'requests' => [
            'index' => [
                'title' => 'Demandes',

                'datagrid' => [
                    'id'                  => 'ID',
                    'product-image'       => 'Image du Produit',
                    'mass-update-error'   => 'Échec de la mise à jour de la demande !',
                    'mass-update-success' => 'Les demandes sélectionnées ont été mises à jour avec succès !',
                    'product-name'        => 'Nom du Produit',
                    'outlet-name'         => 'Nom du Point de Vente',
                    'qty-value'           => 'QTE - :qty',
                    'request-date'        => 'Date de Demande',
                    'requested-qty'       => 'Quantité Demandée',
                    'update-status'       => 'Mettre à Jour le Statut',
                    'user-name'           => 'Nom de l\'Utilisateur',

                    'status' => [
                        'title' => 'Statut',

                        'options' => [
                            'complete' => 'Complète',
                            'decline'  => 'Refusée',
                            'pending'  => 'En Attente',
                        ],
                    ],
                ],
            ],

            'view' => [
                'back-btn'  => 'Retour',
                'btn-title' => 'Sauvegarder',
                'title'     => 'Détails du Produit Demandé #:id',

                'user-info' => [
                    'email'            => 'Email',
                    'name'             => 'Nom',
                    'outlet-address'   => 'Adresse du Point de Vente',
                    'outlet-inventory' => 'Source d\'Inventaire du Point de Vente',
                    'outlet-name'      => 'Nom du Point de Vente',
                    'title'            => 'Informations sur l\'Utilisateur',
                ],

                'request-info' => [
                    'comment'       => 'Commentaire',
                    'product-name'  => 'Nom du Produit',
                    'qty-value'     => 'Quantité - :qty',
                    'request-date'  => 'Date de Demande',
                    'requested-qty' => 'Quantité Demandée',
                    'title'         => 'Informations sur la Demande',

                    'status' => [
                        'title' => 'Statut',

                        'options' => [
                            'complete' => 'Complète',
                            'decline'  => 'Refusée',
                            'pending'  => 'En Attente',
                        ],
                    ],
                ],
            ],

            'update-failed'  => 'Échec de la mise à jour de la demande !',
            'update-success' => 'Demande mise à jour avec succès !',
        ],

        'banks' => [
            'index' => [
                'btn-title' => 'Créer une Banque',
                'title'     => 'Banques',

                'datagrid' => [
                    'active'              => 'Actif',
                    'address'             => 'Adresse de la banque',
                    'agent-name'          => 'Agent',
                    'delete'              => 'Supprimer',
                    'disable'             => 'Désactiver',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Les banques sélectionnées ont été supprimées avec succès!',
                    'name'                => 'Nom de la banque',
                    'status'              => 'Statut',
                ],
            ],

            'create' => [
                'back-btn'  => 'Retour',
                'btn-title' => 'Enregistrer la Banque',
                'title'     => 'Créer une Nouvelle Banque',

                'general' => [
                    'address' => 'Adresse',
                    'email'   => 'Email',
                    'name'    => 'Nom',
                    'phone'   => 'Téléphone',
                    'title'   => 'Général',
                ],

                'agent-and-status' => [
                    'agent'        => 'Assigner un Agent POS',
                    'bank-status'  => 'Statut de la Banque',
                    'select-agent' => 'Sélectionner un Agent',
                    'title'        => 'Agent POS & Statut de la Banque',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Retour',
                'btn-title' => 'Enregistrer la Banque',
                'title'     => 'Modifier la Banque',

                'general' => [
                    'address' => 'Adresse',
                    'email'   => 'Email',
                    'name'    => 'Nom',
                    'phone'   => 'Téléphone',
                    'title'   => 'Général',
                ],

                'agent-and-status' => [
                    'agent'        => 'Assigner un Agent POS',
                    'bank-status'  => 'Statut de la Banque',
                    'select-agent' => 'Sélectionner un Agent',
                    'title'        => 'Agent POS & Statut de la Banque',
                ],
            ],

            'create-success' => 'Banque créée avec succès !',
            'delete-failed'  => 'Échec de la suppression de la Banque !',
            'delete-success' => 'Banque supprimée avec succès !',
            'update-success' => 'Banque mise à jour avec succès !',
        ],

        'sales-reports' => [
            'index' => [
                'title' => 'Rapport de ventes',

                'datagrid' => [
                    'bank-name'      => 'Nom de la Banque',
                    'grand-total'    => 'Total Général',
                    'order-date'     => 'Date de Commande',
                    'order-id'       => 'ID de Commande',
                    'order-id-value' => 'ID - :id',
                    'order-note'     => 'Note de Commande',
                    'outlet-name'    => 'Nom du Point de Vente',
                    'payment-method' => 'Méthode de Paiement',
                    'view'           => 'Voir',

                    'status' => [
                        'title' => 'Statut',

                        'options' => [
                            'canceled'        => 'Annulé',
                            'closed'          => 'Fermé',
                            'completed'       => 'Complété',
                            'fraud'           => 'Fraude',
                            'pending'         => 'En Attente',
                            'pending-payment' => 'Paiement en Attente',
                            'processing'      => 'En Cours',
                        ],
                    ],
                ],
            ],
        ],

        'receipts' => [
            'index' => [
                'create-btn' => 'Créer un Reçu',
                'title'      => 'Reçus',

                'datagrid' => [
                    'delete'              => 'Supprimer',
                    'edit'                => 'Modifier',
                    'id'                  => 'ID',
                    'mass-delete-success' => 'Les reçus sélectionnés ont été supprimés avec succès !',
                    'preview'             => 'Aperçu',
                    'title'               => 'Titre',

                    'status' => [
                        'title' => 'Statut',

                        'options' => [
                            'active'   => 'Actif',
                            'inactive' => 'Inactif',
                        ],
                    ],
                ],
            ],

            'create' => [
                'back-btn'  => 'Retour',
                'btn-title' => 'Enregistrer le Reçu',
                'title'     => 'Créer un Nouveau Reçu',

                'general' => [
                    'cashier-name-label'      => 'Étiquette Nom du Caissier',
                    'change-amount-label'     => 'Étiquette Montant du Rendu',
                    'credit-amount-label'     => 'Étiquette Montant du Crédit',
                    'discount-amt-label'      => 'Étiquette Montant de la Réduction',
                    'display-cashier-name'    => 'Afficher le Nom du Caissier',
                    'display-change-amount'   => 'Afficher le Montant du Rendu',
                    'display-credit-amount'   => 'Afficher le Montant du Crédit',
                    'display-customer-name'   => 'Afficher le Nom du Client',
                    'display-date'            => 'Afficher la Date',
                    'display-discount-amt'    => 'Afficher le Montant de la Réduction',
                    'display-order-id'        => 'Afficher l\'ID de Commande',
                    'display-outlet-address'  => 'Afficher l\'Adresse du Point de Vente',
                    'display-outlet-name'     => 'Afficher le Nom du Point de Vente',
                    'display-sub-total'       => 'Afficher le Sous-Total',
                    'display-tax'             => 'Afficher la Taxe',
                    'grand-total-label'       => 'Étiquette Total Général',
                    'order-id-label'          => 'Étiquette ID de Commande',
                    'receipt-title'           => 'Titre du Reçu',
                    'show-order-barcode'      => 'Afficher le code-barres de commande',
                    'show-print-confirmation' => 'Afficher la confirmation d\'impression',
                    'status'                  => 'Statut',
                    'sub-total-label'         => 'Étiquette Sous-Total',
                    'tax-label'               => 'Étiquette Taxe',
                    'title'                   => 'Général',
                ],

                'logo' => [
                    'display-logo' => 'Afficher le Logo',
                    'logo-alt'     => 'Texte Alternatif du Logo',
                    'logo-height'  => 'Hauteur du Logo (en px)',
                    'logo-width'   => 'Largeur du Logo (en px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Télécharger le Logo',
                ],

                'header' => [
                    'header-content' => 'Contenu de l\'En-tête',
                    'title'          => 'En-tête',
                ],

                'footer' => [
                    'footer-content' => 'Contenu du Pied de Page',
                    'title'          => 'Pied de Page',
                ],
            ],

            'edit' => [
                'back-btn'  => 'Retour',
                'btn-title' => 'Enregistrer le Reçu',
                'title'     => 'Modifier le Reçu',

                'general' => [
                    'cashier-name-label'      => 'Étiquette Nom du Caissier',
                    'change-amount-label'     => 'Étiquette Montant du Rendu',
                    'credit-amount-label'     => 'Étiquette Montant du Crédit',
                    'discount-amt-label'      => 'Étiquette Montant de la Réduction',
                    'display-cashier-name'    => 'Afficher le Nom du Caissier',
                    'display-change-amount'   => 'Afficher le Montant du Rendu',
                    'display-credit-amount'   => 'Afficher le Montant du Crédit',
                    'display-customer-name'   => 'Afficher le Nom du Client',
                    'display-date'            => 'Afficher la Date',
                    'display-discount-amt'    => 'Afficher le Montant de la Réduction',
                    'display-order-id'        => 'Afficher l\'ID de Commande',
                    'display-outlet-address'  => 'Afficher l\'Adresse du Point de Vente',
                    'display-outlet-name'     => 'Afficher le Nom du Point de Vente',
                    'display-sub-total'       => 'Afficher le Sous-Total',
                    'display-tax'             => 'Afficher la Taxe',
                    'grand-total-label'       => 'Étiquette Total Général',
                    'order-id-label'          => 'Étiquette ID de Commande',
                    'receipt-title'           => 'Titre du Reçu',
                    'show-order-barcode'      => 'Afficher le code-barres de commande',
                    'show-print-confirmation' => 'Afficher la confirmation d\'impression',
                    'status'                  => 'Statut',
                    'sub-total-label'         => 'Étiquette Sous-Total',
                    'tax-label'               => 'Étiquette Taxe',
                    'title'                   => 'Général',
                ],

                'logo' => [
                    'display-logo' => 'Afficher le Logo',
                    'logo-alt'     => 'Texte Alternatif du Logo',
                    'logo-height'  => 'Hauteur du Logo (en px)',
                    'logo-width'   => 'Largeur du Logo (en px)',
                    'title'        => 'Logo',
                    'upload-logo'  => 'Télécharger le Logo',
                ],

                'header' => [
                    'header-content' => 'Contenu de l\'En-tête',
                    'title'          => 'En-tête',
                ],

                'footer' => [
                    'footer-content' => 'Contenu du Pied de Page',
                    'title'          => 'Pied de Page',
                ],
            ],

            'preview' => [
                'amount'         => 'Montant',
                'cashier'        => 'Caissier',
                'change-amount'  => 'Montant du Rendu',
                'customer'       => 'Client',
                'customer-email' => 'Email du Client',
                'customer-name'  => 'Nom du Client',
                'customer-phone' => 'Téléphone du Client',
                'date'           => 'Date',
                'discount'       => 'Réduction',
                'email'          => 'Email',
                'grand-total'    => 'Total Général',
                'order-id'       => 'ID de Commande',
                'phone'          => 'Téléphone',
                'price'          => 'Prix',
                'product'        => 'Produit',
                'qty'            => 'Quantité',
                'sub-total'      => 'Sous-Total',
                'tax'            => 'Taxe',
                'title'          => 'Aperçu du Reçu',
                'total-qty'      => 'Quantité Totale',
            ],

            'create-success' => 'Reçu créé avec succès !',
            'delete-failed'  => 'Échec de la suppression du Reçu !',
            'delete-success' => 'Reçu supprimé avec succès !',
            'update-success' => 'Reçu mis à jour avec succès !',
        ],
    ],

    'emails' => [
        'dear'     => 'Cher :name',
        'greeting' => 'Salutations !',

        'registration' => [
            'message' => 'Félicitations ! Votre compte a été créé avec succès. Veuillez vous connecter à votre compte et commencer à utiliser le système POS.',
            'subject' => 'Mail d\'Inscription POS',
        ],
    ],

    'seeders' => [
        'configurations' => [
            'heading-on-login'     => 'Fujiba POS',
            'sub-heading-on-login' => 'Connexion',
            'footer-content'       => '<a href="https://fujiba.shop/" target="_blank" class="underline">Fujiba POS</a>',
            'footer-note'          => '© 2026 Fujiba. All rights reserved.',
        ],
    ],
];
