# Etsy import

The workspace connects its Etsy shop once; its **paid receipts** then import as orders with source « Etsy ».
Online sales do not happen at an event: **Etsy orders have no event**.

| Concept | Meaning |
|---|---|
| Etsy app | Each workspace brings its own Etsy developer app: keystring on `Workspace`, shared secret as an encrypted workspace secret (like SumUp); the app must declare the callback `<site>/parametres/etsy/retour` |
| Connection | OAuth 2 with PKCE (scopes `transactions_r shops_r`); the shop id/name and token expiry are on `Workspace`, the access and refresh tokens are encrypted workspace secrets |
| Etsy listing | An Etsy listing (+ its variation text) seen in a receipt, remembered with the product/variant it sells once linked (`Domain\Etsy\EtsyListing`) |

Model: `src/Application/Etsy/`, `src/Domain/Etsy/`, adapters `src/Infrastructure/Etsy/` (`FakeEtsyGateway` in the test env).

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| Y1 | Connecting goes through Etsy's consent page; the `state` returned must match the one kept in the session, otherwise the connection is refused | `EtsyConnectionController` | `EtsyConnectionTest` |
| Y1b | Saving other app keys (keystring or shared secret) disconnects the shop: its tokens belong to the previous app | `UpdateEtsySettingsHandler` | `EtsyUseCasesTest` |
| Y2 | The access token (1 h) is renewed with the refresh token when it expires; disconnecting forgets both tokens and the shop, imported orders stay | `EtsySession`, `DisconnectEtsyHandler` | `EtsyUseCasesTest` |
| Y3 | An import reads every paid receipt, oldest first; a receipt already imported (`ETSY-<receipt id>`, unique per workspace) is skipped | `ImportFromEtsyHandler` | `EtsyUseCasesTest` |
| Y4 | A receipt line is matched to a product: the listing's link if any, else **SKU = product reference**, else **title = product display name**; a product with variants needs the variation to equal one of its variants | `EtsyItemResolver` | `EtsyUseCasesTest` |
| Y5 | A receipt with an unmatched line is not imported: its listing is remembered « à associer ». Once linked to a product/variant, the next import brings the receipt in | `EtsyItemResolver`, `LinkEtsyListingHandler` | `EtsyUseCasesTest`, `etsy.spec.js` |
| Y6 | An Etsy order has no event; items at the price paid on Etsy; the Etsy discount is kept as « Remise Etsy »; the **shipping charged** is part of the order total (so of the turnover); payment « Carte »; stock is taken like any sale | `Order::importFromEtsy()` | `OrderTest`, `EtsyUseCasesTest` |

## Use cases & API

| Use case | Endpoint |
|---|---|
| `UpdateEtsySettings` | `PUT /api/etsy/settings` `{keystring, sharedSecret?}` |
| Connect | `GET /parametres/etsy/connexion` → Etsy → `GET /parametres/etsy/retour` → `/parametres?etsy=connecte\|refuse\|erreur\|indisponible` |
| `DisconnectEtsy` | `DELETE /api/etsy/connection` |
| `ImportFromEtsy` | `POST /api/etsy/import` → `{ordersImported, ordersAlreadyImported, ordersWaitingForListings, listingsToLink}` |
| `ListEtsyListings` / `LinkEtsyListing` | `GET /api/etsy/listings`, `PUT /api/etsy/listings/{id}` `{productId, variant}` |

## UI

- **Paramètres › Etsy**: the callback address to declare in the Etsy app, « Keystring » and « Shared secret » (kept encrypted, shown as ••••1234), « Enregistrer les clés »; then « Connecter ma boutique Etsy », or the shop name and « Déconnecter la boutique » when connected.
- **Commandes**: « Importer depuis Etsy » (when connected); a notice « N annonces Etsy à associer » opens « Annonces Etsy »: one row per listing (title, variation) with a product combobox and a variant select (pre-selected when the variation matches), « Associer », then « Relancer l'import Etsy ». Etsy orders show « Boutique Etsy » instead of an event and an « Etsy » badge; on a day with both stand and Etsy sales, the list shows one group per source (event first, then Etsy), each with its own count and total; the order page shows « Frais de port ».
