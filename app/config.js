//config.js
import ApolloClient, { createNetworkInterface } from 'apollo-client';
import { PolymerApollo } from 'polymer-apollo';

// Create the apollo client
const apolloClient = new ApolloClient({
  networkInterface: createNetworkInterface({
    uri: 'http://localhost:8080/graphql',
    transportBatching: true,
  }),
});

//create a new polymer behavior from PolymerApollo class.
export const PolymerApolloBehavior = new PolymerApollo({apolloClient})