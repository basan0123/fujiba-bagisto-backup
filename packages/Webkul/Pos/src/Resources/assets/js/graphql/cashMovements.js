import { gql } from '@apollo/client/core';

export const RECORD_CASH_MOVEMENT = gql`
    mutation recordCashMovement($input: CashMovementInput!) {
        recordCashMovement(input: $input) {
            success
            message
            errors
            movement {
                id
                type
                category
                description
                amount
                currency
                movementDate
            }
        }
    }
`;
