import { gql } from '@apollo/client/core';

export const FETCH_GLOBAL_DATA = gql`
    query fetchGlobalData {
        fetchGlobalData {
            categories {
                id
                slug
                name
                parentId
                children {
                    id
                    slug
                    name
                    parentId
                    children {
                        id
                        slug
                        name
                        parentId
                    }
                }
            }
            locales {
                code
                name
                direction
            }
            currencies {
                id
                code
                name
                symbol
                decimal
                groupSeparator
                decimalSeparator
                currencyPosition
            }
            exchangeRates {
                id
                rate
                targetCurrency
                createdAt
                updatedAt
                currency {
                    id
                    code
                    name
                    symbol
                    decimal
                    groupSeparator
                    decimalSeparator
                    currencyPosition
                }
            }
            taxCategories {
                id
                code
                name
                description
                taxRates {
                    id
                    identifier
                    isZip
                    zipCode
                    zipFrom
                    zipTo
                    state
                    country
                    taxRate
                }
            }
            configurations {
                code
                value
            }
            countries {
                id
                code
                name
            }
            countryStates {
                countryCode
                states {
                    id
                    countryId
                    countryCode
                    code
                    defaultName
                }
            }
            baseCurrency
            defaultLocale
            defaultCountry
        }
    }
`;
