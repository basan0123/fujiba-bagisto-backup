import { gql } from '@apollo/client/core';

export const OPEN_DRAWER = gql`
    mutation openDrawer (
        $input: drawerInput!
    ) {
        openDrawer (
            input: $input
        ) {
            success
            errors
            message
        }
    }
`;

export const GET_DRAWER = gql`
    query getDrawerDetails {
        getDrawerDetails {
            openingAmount
            cashPaymentSale
            otherPaymentSale
            expectedDrawerAmount
            differenceAmount
            remark
        }
    }
`;

export const CLOSE_DRAWER = gql`
    mutation closeDrawer (
        $input: drawerInput!
    ) {
        closeDrawer (
            input: $input
        ) {
            success
            errors
            message
        }
    }
`;

export const GET_TODAY_SALE = gql`
    query getTodaySales (
        $page: Int!,
        $first: Int!,
        $filters: TodaySalesFilterInput,
    ) {
        getTodaySales (
            page: $page,
            first: $first,
            filters: $filters,
        ) {
            paginatorInfo {
                count
                currentPage
                lastPage
                total
            }
            todaySale {
                openingAmount
                cashPaymentSale
                otherPaymentSale
            }
            data {
                orderId
                orderTotal
                paymentMode
                createdAt
            }
        }
    }
`;

export const GET_SALE_HISTORY = gql`
    query getSaleHistory (
        $page: Int!,
        $first: Int!,
        $filters: SaleHistoryFilterInput,
    ) {
        getSaleHistory (
            page: $page,
            first: $first,
            filters: $filters,
        ) {
            paginatorInfo {
                count
                currentPage
                lastPage
                total
            }
            data {
                date
                cashPayment
                otherPayment
                totalSale
                drawerNote
            }
        }
    }
`;
