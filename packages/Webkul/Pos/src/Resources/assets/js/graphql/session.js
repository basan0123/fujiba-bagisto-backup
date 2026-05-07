import { gql } from '@apollo/client/core';

export const LOGIN = gql`
    mutation agentLogin($input: AgentLoginInput!) {
        agentLogin(input: $input) {
            success
            errors
            message
            accessToken
            agent {
                id
                outletId
                username
                firstName
                lastName
                email
                image
                imageUrl
                reportType
                status
                trainingMode
                trainingPrintReceipt
                createdAt
                updatedAt
                outlet {
                    id
                    name
                    email
                    phone
                    website
                    customerCareNumber
                    gstNumber
                    lowStockQty
                    address
                    country
                    state
                    city
                    postcode
                    status
                    receiptId
                    inventorySourceId
                    createdAt
                    updatedAt
                    receipt {
                        id
                        title
                        status
                        displayOutletName
                        showOrderBarcode
                        showPrintConfirmation
                        displayDate
                        displayOrderId
                        orderIdLabel
                        displayCustomerName
                        displaySubTotal
                        subTotalLabel
                        displayDiscount
                        discountLabel
                        displayTax
                        taxLabel
                        displayCreditAmount
                        creditAmountLabel
                        displayChangeAmount
                        creditChangeLabel
                        displayCashierName
                        cashierLabel
                        displayOutletAddress
                        grandTotalLabel
                        displayLogo
                        logo
                        logoUrl
                        logoWidth
                        logoHeight
                        logoAlt
                        headerContent
                        footerContent
                        createdAt
                        updatedAt
                    }
                }
                banks {
                    id
                    name
                }
            }
        }
    }
`;

export const FETCH_CURRENT_AGENT = gql`
    query fetchCurrentAgent {
        fetchCurrentAgent {
            id
            outletId
            username
            firstName
            lastName
            email
            image
            imageUrl
            reportType
            status
            createdAt
            updatedAt
            outlet {
                id
                name
                email
                phone
                website
                customerCareNumber
                gstNumber
                lowStockQty
                address
                country
                state
                city
                postcode
                status
                receiptId
                inventorySourceId
                createdAt
                updatedAt
                receipt {
                    id
                    title
                    status
                    displayOutletName
                    showOrderBarcode
                    showPrintConfirmation
                    displayDate
                    displayOrderId
                    orderIdLabel
                    displayCustomerName
                    displaySubTotal
                    subTotalLabel
                    displayDiscount
                    discountLabel
                    displayTax
                    taxLabel
                    displayCreditAmount
                    creditAmountLabel
                    displayChangeAmount
                    creditChangeLabel
                    displayCashierName
                    cashierLabel
                    displayOutletAddress
                    grandTotalLabel
                    displayLogo
                    logo
                    logoUrl
                    logoWidth
                    logoHeight
                    logoAlt
                    headerContent
                    footerContent
                }
            }
            banks {
                id
                name
            }
        }
    }
`;

export const LOGOUT = gql`
    mutation agentLogout {
        agentLogout {
            success
            message
        }
    }
`;

export const SET_AGENT_TRAINING = gql`
    mutation setAgentTraining ($input: SetAgentTrainingInput!) {
        setAgentTraining (input: $input) {
            success
            message
            trainingMode
            trainingPrintReceipt
        }
    }
`;