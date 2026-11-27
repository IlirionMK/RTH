The RTH API stores trips, their points and segments, draws routes and counts travel statistics. The web app and the mobile app use it.

## Authentication

Register with `POST /users` and confirm the email address with the link from the email. Then send `POST /tokens` with the email, the password and a device name. Put the token from the answer into every request:

```
Authorization: Bearer <token>
```

`DELETE /tokens/current` signs out on this device only. Endpoints without a lock icon work without a token.

## Language

Send `Accept-Language: pl` or `Accept-Language: en`. Polish is the default. Validation messages and errors come in this language.

## Errors

Every error is JSON with a `message`. A validation error also names the fields:

```json
{
  "message": "The name field is required.",
  "errors": { "name": ["The name field is required."] }
}
```

| Code | Meaning |
|---|---|
| 401 | No token or the token is not valid. |
| 403 | The token is valid, but the user has no rights to this resource. |
| 404 | The resource does not exist. A private profile answers like a missing one. |
| 409 | The resource is in a state that does not allow the change. |
| 422 | The input is not valid. |
| 429 | Too many requests. Wait and send the request again. |
| 503 | An external service (place search) does not answer. |

## Pages

Lists return 15 items. The answer has `links` with the next and previous pages and `meta` with `current_page`, `last_page` and `total`. Ask for another page with `?page=2`.

## Limits

- Registration and sign-in: 5 requests a minute.
- Email confirmation links: 6 requests a minute.
- Place search: 30 requests a minute.

## Maps

Lines are GeoJSON `LineString` with `[longitude, latitude]` pairs. A trip shows the full line of each segment. The map of all trips uses a simplified line (about 500 m precision). Country codes are ISO 3166-1 alpha-2.
