import { gql } from '@apollo/client/core';

export const PROFILE_UPDATE = gql`
    mutation updateProfile (
        $input: UpdateProfileInput!
    ) {
        updateProfile (
            input: $input
        ) {
            success
            errors
            message
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
