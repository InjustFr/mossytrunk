# Etsy import

The workspace connects its Etsy shop once; its **paid receipts** then go through the generic [import](imports.md). This page lists what is specific to Etsy.

Configuration: « Paramètres › Services connectés › Ajouter un service › Etsy »: each workspace brings its own Etsy developer app — « Keystring » and « Shared secret » (stored encrypted); the app must declare the return address shown in the form, `<site>/parametres/etsy/retour`. Then « Connecter la boutique ».
Defaults: sales **online** (no event), unknown items **ask me**, line prices **listed**.

Code: `src/Infrastructure/Connector/Etsy/` — `EtsyConnector` (`AuthorizingConnector`), `EtsyApiGateway`, `EtsyPayloadMapper`, `FakeEtsyGateway` (test env: fake consent that redirects straight back, fixture `tests/Fixtures/etsy/receipts.json`).

## What is read from Etsy

- OAuth 2 with PKCE (scopes `transactions_r shops_r`); the shop id/name and token expiry are on the service connection, the access and refresh tokens are encrypted workspace secrets.
- `GET /v3/application/shops/{shop}/receipts?was_paid=true` (pages of 100).
- Mapping: receipt id = external id, reference `ETSY-<receipt id>`; line = listing id (external reference), title, SKU, variations joined with « / », quantity, price; charged = Σ lines − `discount_amt`; shipping = `total_shipping_cost`; payment « Carte ».

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| Y1 | Connecting goes through Etsy's consent page; the `state` returned must match the one kept in the session, otherwise the connection is refused | `ConnectServiceController`, `ServiceCallbackController`, `AuthorizationFlow` | `ServiceAuthorizationTest` |
| Y1b | Saving other app keys disconnects the shop (see [imports.md](imports.md) I2) | `UpdateConnectionHandler` | `ServiceConnectionUseCasesTest` |
| Y2 | The access token (1 h) is renewed with the refresh token when it expires, and saved at once; disconnecting forgets both tokens and the shop, imported orders stay | `ConnectionSession`, `DisconnectServiceHandler` | `ImportEtsySalesTest` |
| Y3 | A receipt already imported is skipped (see I3) | `ImportSalesHandler` | `ImportEtsySalesTest` |
| Y4 | A receipt line is matched by the listing's link, else **SKU = product reference**, else **title = product display name**; a product with variants needs the variation to equal one of its variants (see I5) | `ExternalItemResolver` | `ImportEtsySalesTest`, `ImportPoliciesTest` |
| Y5 | A receipt with an unmatched line waits; its listing is remembered « à associer » (see I6) | `ExternalItemResolver`, `LinkExternalItemHandler` | `ImportEtsySalesTest`, `etsy.spec.js` |
| Y6 | Items at the price paid on Etsy; the Etsy discount is kept as « Remise Etsy »; the **shipping charged** is part of the order total (so of the turnover); stock is taken like any sale | `Order::imported()`, `LinePrices::Listed` | `OrderTest`, `ImportEtsySalesTest` |
