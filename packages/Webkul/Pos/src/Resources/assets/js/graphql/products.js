import { gql } from '@apollo/client/core';

export const GET_REQUESTED_PRODUCTS = gql`
    query getRequestedProducts(
        $page: Int!,
        $first: Int!,
        $query: String!
    ) {
        getRequestedProducts(
            page: $page,
            first: $first,
            query: $query
        ) {
            paginatorInfo {
                count
                currentPage
                lastPage
                total
            }
            data {
                id
                name
                comment
                requestedQuantity
                requestStatus
                createdAt
                updatedAt
            }
        }
    }
`;

export const GET_LOW_STOCK_PRODUCTS = gql`
    query getLowStockProducts {
        getLowStockProducts {
            id
            sku
            type
            name
            quantity
            price
            priceHtml
            images {
                id
                url
            }
        }
    }
`;

export const REQUEST_PRODUCT_QTY = gql`
    mutation requestProductQty ($input: requestProductQtyInput!) {
        requestProductQty (input: $input) {
            success
            message
        }
    }
`;

export const LINK_BARCODE_TO_PRODUCT = gql`
    mutation linkBarcodeToProduct ($input: linkBarcodeToProductInput!) {
        linkBarcodeToProduct (input: $input) {
            success
            message
            productId
            gtin
            scanValue
        }
    }
`;

export const SET_PRODUCT_IMAGE = gql`
    mutation setOutletProductImage ($input: setOutletProductImageInput!) {
        setOutletProductImage (input: $input) {
            success
            message
            product {
                id
                images {
                    id
                    url
                }
            }
        }
    }
`;

export const DELETE_PRODUCT_IMAGE = gql`
    mutation deleteOutletProductImage ($input: deleteOutletProductImageInput!) {
        deleteOutletProductImage (input: $input) {
            success
            message
            product {
                id
                images {
                    id
                    url
                }
            }
        }
    }
`;

export const BULK_UPDATE_PRODUCTS = gql`
    mutation bulkUpdateOutletProducts($input: bulkUpdateOutletProductsInput!) {
        bulkUpdateOutletProducts(input: $input) {
            success
            updated
            message
        }
    }
`;

export const CREATE_PRODUCT = gql`
    mutation createOutletProduct ($input: createOutletProductInput!) {
        createOutletProduct (input: $input) {
            success
            errors
            message
            product {
                id
                sku
                type
                name
                quantity
                price
                convertedPrice
                taxCategoryId
                barcode
                gtin
                scanType
                scanValue
                priceHtml
                images {
                    id
                    url
                }
                categories {
                    id
                    name
                    parentId
                    children {
                        id
                        name
                        parentId
                    }
                }
                customizableOptions {
                    id
                    label
                    productId
                    type
                    isRequired
                    maxCharacters
                    supportedFileExtensions
                    sortOrder
                    product {
                        id
                        sku
                        name
                        type
                    }
                    translations {
                        id
                        locale
                        label
                        productCustomizableOptionId
                    }
                    customizableOptionPrices {
                        id
                        isDefault
                        isUserDefined
                        label
                        price
                        productCustomizableOptionId
                        qty
                        sortOrder
                    }
                }
                options {
                    id
                    label
                    type
                    isRequired
                    sortOrder
                    products {
                        id
                        qty
                        name
                        productId
                        isDefault
                        sortOrder
                        inStock
                        inventory
                        price {
                            regular {
                                price
                                formattedPrice
                            }
                            final {
                                price
                                formattedPrice
                            }
                        }
                    }
                }
            }
        }
    }
`;
