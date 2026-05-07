import { ApolloClient, createHttpLink, InMemoryCache } from '@apollo/client/core'
import { DefaultApolloClient } from '@vue/apollo-composable'
import { setContext } from '@apollo/client/link/context';
import { ApolloLink } from '@apollo/client/core';
import { onError } from '@apollo/client/link/error';
import { useCookies } from '@src/composable/cookies';
import useGlobalLoader from '@src/composable/global-loader';

export default {
    install: (app) => {
        /**
         * Get the cookies composable
         */
        const cookies = useCookies();
        const { showLoader, hideLoader } = useGlobalLoader();

        /**
         * Create a loader link to show and hide the global loader
         * during the request lifecycle
         */
        const loaderLink = new ApolloLink((operation, forward) => {
            /**
             * Show the loader if the operation is in the list of operations
             * that require a loader
             */
            const loaderEnabledOperations = [
                'fetchGlobalData',
                'agentLogout',
                'syncOrder',
                'returnOrder',
            ];

            if (loaderEnabledOperations.includes(operation.operationName)) {
                showLoader();

                return forward(operation).map(response => {
                    hideLoader();

                    return response;
                });
            }

            return forward(operation);
        });

        /**
         * Create a context link to add headers to each request
         */
        const authLink = setContext((_, { headers }) => {
            const accessToken = localStorage.getItem('accessToken');

            return {
                headers: {
                    ...headers,
                    'Authorization': accessToken ? `Bearer ${accessToken}` : null,
                    'x-locale': JSON.parse(cookies.get('locale'))?.code || null,
                    'x-currency': JSON.parse(cookies.get('currency'))?.code || null,
                }
            };
        });

        /**
         * Apollo Link to remove __typename from all responses
         */
        function removeTypename(obj) {
            if (Array.isArray(obj)) {
                return obj.map(removeTypename);
            } else if (
                obj
                && typeof obj === 'object'
            ) {
                const newObj = {};

                for (const key in obj) {
                    if (key !== '__typename') {
                        newObj[key] = removeTypename(obj[key]);
                    }
                }

                return newObj;
            }

            return obj;
        }

        const removeTypenameLink = new ApolloLink((operation, forward) => {
            return forward(operation).map(response => {
                if (response.data) {
                    response.data = removeTypename(response.data);
                }

                return response;
            });
        });

        /**
         * Create an error link to handle unauthenticated errors globally
         */
        const errorLink = onError(({ graphQLErrors, networkError }) => {
            const isGraphQLUnauthenticated = graphQLErrors?.some(
                err => err.message === 'Unauthenticated.' || err.extensions?.category === 'authentication'
            );

            const isHttp401 = networkError && 'statusCode' in networkError && networkError.statusCode === 401;

            if (isGraphQLUnauthenticated || isHttp401) {
                localStorage.removeItem('accessToken');

                window.location.href = `${document.querySelector('meta[name="base-url"]').getAttribute('content')}/pos/`;
            }
        });

        /**
         * Create a new HttpLink instance
         */
        const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content');

        const httpLink = createHttpLink({
            uri: `${baseUrl}/graphql`,
        });

        /**
         * Create a new Apollo Client instance
         */
        const apolloClient = new ApolloClient({
            link: ApolloLink.from([
                errorLink,
                loaderLink,
                removeTypenameLink,
                authLink,
                httpLink
            ]),
            cache: new InMemoryCache(),
            defaultOptions: {
                query: {
                    fetchPolicy: 'no-cache',
                    errorPolicy: 'all',
                },
                watchQuery: {
                    fetchPolicy: 'no-cache',
                    errorPolicy: 'ignore',
                },
            },
        });

        app.provide(DefaultApolloClient, apolloClient);
    }
};
