//config.js
import ApolloClient, { createNetworkInterface, addTypename } from 'apollo-client';

// Create the apollo client
export const apolloClient = new ApolloClient({
  networkInterface: createNetworkInterface({
    uri: 'http://localhost/graphql',
    transportBatching: true,
  })
});