import { gql } from '@apollo/client/core';

export const GET_SALE_REPORT = gql`
    query getSaleReport (
        $startDate: String
        $endDate: String
    ) {
        getSaleReport (
            startDate: $startDate
            endDate: $endDate
        ) {
            reports {
                ordersCount {
                    name
                    total
                    progress
                    goingUp
                    series {
                        name
                        data
                    }
                    labels
                }
                avgOrderValue {
                    name
                    total
                    progress
                    goingUp
                    series {
                        name
                        data
                    }
                    labels
                }
                avgItemsPerOrder {
                    name
                    total
                    progress
                    goingUp
                    series {
                        name
                        data
                    }
                    labels
                }
                discountOffers {
                    name
                    total
                    progress
                    goingUp
                    series {
                        name
                        data
                    }
                    labels
                }
                cashPayments {
                    name
                    total
                    progress
                    goingUp
                    series {
                        name
                        data
                    }
                    labels
                }
                otherPayments {
                    name
                    total
                    progress
                    goingUp
                    series {
                        name
                        data
                    }
                    labels
                }
            }
        }
    }
`;