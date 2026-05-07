import { gql } from '@apollo/client/core';

export const CUSTOMERS = gql`
    query getCustomers($page: Int!, $first: Int!) {
        getCustomers(page: $page, first: $first) {
            paginatorInfo {
                count
                currentPage
                lastPage
                total
            }
            data {
                id
                firstName
                lastName
                name
                gender
                dateOfBirth
                email
                phone
                image
                imageUrl
                status
                channelId
                isVerified
                isSuspended
                createdAt
                updatedAt
                addresses {
                    id
                    addressType
                    defaultAddress
                    address
                    city
                    state
                    country
                    postcode
                    createdAt
                    updatedAt
                }
            }
        }
    }
`;

export const CREATE = gql`
    mutation createPosCustomer (
        $input: createCustomerInput!
    ) {
        createPosCustomer (
            input: $input
        ) {
            success
            errors
            message
            customer {
                id
                firstName
                lastName
                name
                gender
                dateOfBirth
                email
                phone
                image
                imageUrl
                status
                channelId
                isVerified
                isSuspended
                createdAt
                updatedAt
                addresses {
                    id
                    addressType
                    defaultAddress
                    address
                    city
                    state
                    country
                    postcode
                    createdAt
                    updatedAt
                }
            }
        }
    }
`;

export const UPDATE = gql`
    mutation updatePosCustomer (
        $input: updateCustomerInput!
    ) {
        updatePosCustomer (
            input: $input
        ) {
            success
            errors
            message
            customer {
                id
                firstName
                lastName
                name
                gender
                dateOfBirth
                email
                phone
                image
                imageUrl
                status
                channelId
                isVerified
                isSuspended
                createdAt
                updatedAt
                addresses {
                    id
                    addressType
                    defaultAddress
                    address
                    city
                    state
                    country
                    postcode
                    createdAt
                    updatedAt
                }
            }
        }
    }
`;

export const DELETE = gql`
    mutation deletePosCustomer($id: ID!) {
        deletePosCustomer(id: $id) {
            success
            message
        }
    }
`;