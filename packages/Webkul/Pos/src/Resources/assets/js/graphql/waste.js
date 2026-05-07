import { gql } from '@apollo/client/core';

export const RECORD_WASTE = gql`
    mutation recordWaste($input: WasteInput!) {
        recordWaste(input: $input) {
            success
            message
            errors
            entry {
                id
                productName
                productSku
                quantity
                unitCost
                totalCost
                reason
                notes
                status
                wastedAt
            }
        }
    }
`;

export const GET_WASTE_ENTRIES = gql`
    query getWasteEntries {
        getWasteEntries {
            id
            productName
            productSku
            quantity
            totalCost
            reason
            status
            wastedAt
        }
    }
`;
