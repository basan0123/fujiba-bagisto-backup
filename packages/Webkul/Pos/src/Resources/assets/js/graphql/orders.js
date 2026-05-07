import { gql } from '@apollo/client/core';

export const ORDERS = gql`
    query getOrders ($page: Int!, $first: Int!) {
        getOrders (page: $page, first: $first) {
            paginatorInfo {
                count
                currentPage
                lastPage
                total
            }
            data {
                id
                barcodeUrl
                outlet {
                    name
                    email
                    phone
                    agent {
                        email
                        firstName
                        lastName
                    }
                }
                customerCredit {
                    changeAmount
                    tenderedAmount
                }
                order {
                    id
                    status
                    customerFirstName
                    customerLastName
                    customerEmail
                    totalQtyOrdered
                    discountAmount
                    taxAmount
                    subTotal
                    grandTotal
                    createdAt
                    customer {
                        id
                        name
                        email
                        phone
                    }
                    items {
                        id
                        sku
                        name
                        qtyOrdered
                        qtyInvoiced
                        qtyRefunded
                        price
                        total
                        additional
                    }
                }
            }
        }
    }
`;

export const SYNC_ORDER = gql`
    mutation syncOrder ($input: syncOrderInput!) {
        syncOrder (input: $input) {
            success
            message
            outletOrder {
                id
                barcodeUrl
                outlet {
                    name
                    email
                    phone
                    agent {
                        email
                        firstName
                        lastName
                    }
                }
                customerCredit {
                    changeAmount
                    tenderedAmount
                }
                order {
                    id
                    status
                    customerFirstName
                    customerLastName
                    customerEmail
                    totalQtyOrdered
                    discountAmount
                    taxAmount
                    subTotal
                    grandTotal
                    createdAt
                    customer {
                        id
                        name
                        email
                        phone
                    }
                    items {
                        id
                        sku
                        name
                        qtyOrdered
                        qtyInvoiced
                        qtyRefunded
                        price
                        total
                        additional
                    }
                }
            }
        }
    }
`;

export const RETURN_ORDER = gql`
    mutation returnOrder ($input: returnOrderInput!) {
        returnOrder (input: $input) {
            success
            message
            outletOrder {
                id
                barcodeUrl
                outlet {
                    name
                    email
                    phone
                    agent {
                        email
                        firstName
                        lastName
                    }
                }
                customerCredit {
                    changeAmount
                    tenderedAmount
                }
                order {
                    id
                    status
                    customerFirstName
                    customerLastName
                    customerEmail
                    totalQtyOrdered
                    discountAmount
                    taxAmount
                    subTotal
                    grandTotal
                    createdAt
                    customer {
                        id
                        name
                        email
                        phone
                    }
                    items {
                        id
                        sku
                        name
                        qtyOrdered
                        qtyInvoiced
                        qtyRefunded
                        price
                        total
                        additional
                    }
                }
            }
        }
    }
`;
