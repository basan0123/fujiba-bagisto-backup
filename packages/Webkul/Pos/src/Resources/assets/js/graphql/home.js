import { gql } from '@apollo/client/core';

export const PRODUCTS = gql`
    query getOutletProducts ($page: Int!, $first: Int!) {
        getOutletProducts (page: $page, first: $first) {
            paginatorInfo {
                count
                currentPage
                lastPage
                total
            }
            data {
                id
                sku
                type
                name
                quantity
                weight
                price
                specialPrice
                specialPriceFrom
                specialPriceTo
                convertedPrice
                taxCategoryId
                status
                posStatus
                barcode
                gtin
                scanType
                scanValue
                priceHtml
                superAttributes {
                    id
                    code
                    adminName
                    type
                    options {
                        id
                        adminName
                    }
                }
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
                booking {
                    id
                    type
                    qty
                    location
                    showLocation
                    availableEveryWeek
                    availableFrom
                    availableTo
                    productId
                    defaultSlot {
                        id
                        bookingType
                        duration
                        breakTime
                        slotManyDays {
                            id
                            to
                            from
                            toDay
                            fromDay
                        }
                        slotOneDay {
                            id
                            to
                            from
                            status
                        }
                        bookingProductId
                    }
                    appointmentSlot {
                        id
                        duration
                        breakTime
                        sameSlotAllDays
                        slotManyDays {
                            to
                            from
                        }
                        slotOneDay {
                            id
                            to
                            from
                        }
                        bookingProductId
                    }
                    eventTickets {
                        id
                        price
                        qty
                        specialPrice
                        specialPriceFrom
                        specialPriceTo
                        bookingProductId
                        translations {
                            locale
                            name
                            description
                        }
                    }
                    rentalSlot {
                        id
                        rentingType
                        dailyPrice
                        hourlyPrice
                        sameSlotAllDays
                        bookingProductId
                        slotManyDays {
                            to
                            from
                        }
                        slotOneDay {
                            id
                            to
                            from
                        }
                    }
                    tableSlot {
                        id
                        priceType
                        guestLimit
                        duration
                        breakTime
                        preventSchedulingBefore
                        sameSlotAllDays
                        bookingProductId
                        slotManyDays {
                            to
                            from
                        }
                        slotOneDay {
                            id
                            to
                            from
                        }
                    }
                    product {
                        id
                        sku
                        name
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
                variantConfigurations
                variants {
                    id
                    sku
                    type
                    name
                    size
                    color
                    quantity
                    price
                    convertedPrice
                    barcode
                    gtin
                    scanType
                    scanValue
                    priceHtml
                    superAttributes {
                        id
                        code
                        adminName
                        type
                        options {
                            id
                            adminName
                        }
                    }
                }
                downloadableSamples {
                    id
                    url
                    file
                    fileName
                    fileUrl
                    type
                    sortOrder
                    productId
                    createdAt
                    updatedAt
                    translations {
                        id
                        locale
                        title
                        productDownloadableSampleId
                    }
                }
                downloadableLinks {
                    id
                    title
                    price
                    url
                    file
                    fileUrl
                    fileName
                    type
                    sampleUrl
                    sampleFile
                    sampleFileName
                    sampleFileUrl
                    sampleType
                    sortOrder
                    productId
                    downloads
                    translations {
                        id
                        locale
                        title
                        productDownloadableLinkId
                    }
                }
                groupedProducts {
                    id
                    qty
                    sortOrder
                    productId
                    associatedProductId
                    associatedProduct {
                        id
                        type
                        name
                        attributeFamilyId
                        sku
                        parentId
                        isSaleable
                        priceHtml {
                            minPrice
                            priceHtml
                        }
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
