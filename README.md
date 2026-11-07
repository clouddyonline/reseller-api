# Reseller API

## Base URL and request format

The base URL is:

```text
https://console.clouddy.online/reseller/api
```

Every endpoint requires **POST** and a `token` field in the POST body. The API token is a 32-character hexadecimal value; find it on the reseller cabinet dashboard. A browser session is not required.

Send ordinary fields as `application/x-www-form-urlencoded` or `multipart/form-data`. JSON request bodies are not supported. Use `multipart/form-data` when uploading an M3U file.

The `page`, `find`, `by`, and `order` values are read from the URL query string, even though the HTTP method is POST. List endpoints return `items` and `paging`; the default page is 1 and the page size is 25.

## Rate limit

The rate limit is 60 requests per minute per source IP address, with a burst allowance of up to 10 additional requests. Requests beyond the limit receive HTTP `429`.

Example:

```http
POST /reseller/api/users/2?find[state]=active&by=id&order=DESC
Content-Type: application/x-www-form-urlencoded

token=<reseller-token>
```

## Authentication and common responses

Every request must include:

| Field | Location | Type | Required | Description |
| --- | --- | --- | --- | --- |
| `token` | POST body | string | Yes | Reseller API token. |

Successful responses use HTTP `200` and JSON. Common error statuses are `400` for invalid arguments or unavailable operations, `403` for a token that does not match an active reseller, `429` when the rate limit is exceeded, and `500` for a processing or payment initialization error. A non-POST request receives `400`. See [Response examples](#response-examples) for the JSON response formats.

## Endpoints

In the tables below, `{id}` and similar path values are URL path arguments. All fields listed as POST fields go in the body alongside `token`.

### Dashboard and reseller profile

| Method and URL | Arguments and fields | Behavior |
| --- | --- | --- |
| `POST /reseller/api/info` | No arguments. | Returns reseller information, user status counts, online counts, and TV/VOD playlist counts. |
| `POST /reseller/api/profile/edit` | Optional POST fields: `password` (string), `company` (string), `contacts` (string), `timezone` (string). At least one accepted field must be supplied. | Updates the reseller profile. A successful response includes the current token and `updated: true`. Use the returned token after changing the password. |

### Users

| Method and URL | Arguments and fields | Behavior |
| --- | --- | --- |
| `POST /reseller/api/users` | Optional path page: `/reseller/api/users/{page}`. Optional query filters: `find[id]`, `find[email]`, `find[status]`, `find[state]`, `find[tariff]`, `find[notes]`. Optional query sort: `by` accepts `id`, `start`, or `end`; `order` accepts `ASC` or `DESC`. | Returns the reseller's users, including their account password and PIN. The default sort is by ID ascending. |
| `POST /reseller/api/users/add` | Required POST field: `login` (username part only; do not include `@` or the reseller domain). Optional: `password`, `pin`, `timezone`, `notes` (strings). | Creates a user under the reseller's domain. If `password` is omitted or empty, one is generated; if `pin` is omitted or empty, it defaults to `0000`. The response fields are `user` (created user's ID), `email`, `password`, and `created: true`. |
| `POST /reseller/api/users/edit/{id}` | Path: user `id`. Optional POST fields: `password`, `status`, `pin`, `timezone`, `notes`. `status` accepts `0` (blocked) or `1` (active); promo status cannot be newly assigned. | Updates a reseller-owned user. Response: `user`, `updated: true`. |
| `POST /reseller/api/users/delete/{id}` | Path: user `id`. | Deletes the user only when it is an empty trial user with no devices. Response: `user`, `deleted: true`. |
| `POST /reseller/api/users/tariffs/{id}` | Path: user `id`. | Returns a top-level JSON array of tariffs available to the user and the user's current tariff, including reseller-adjusted cost. |
| `POST /reseller/api/users/renew/{id}` | Path: user `id`. | Attempts renewal when the user's current state, tariff, and reseller deposit satisfy the renewal rules. Success response: `user`, `renewed: true`. |
| `POST /reseller/api/users/cancel/{id}` | Path: user `id`. | Clears the tariff for a non-trial suspended user. Success response: `user`, `cancelled: true`. |
| `POST /reseller/api/users/activate/{id}/{tariff}` | Path: user `id`, tariff `tariff`. | Activates a public tariff for a reseller-owned user. Success response: `user`, `tariff`, `activated: true`. |
| `POST /reseller/api/users/refill/{id}/{tariff}` | Path: user `id`, tariff `tariff`. Required POST field: `via` — use `deposit` or a value returned by `POST /reseller/api/refill/vias`. For external providers, also provide `success` and `failed` absolute HTTP(S) URLs; optional `cancel` URL defaults to `failed`. | Starts a payment for the calculated amount or charges the reseller deposit when `via=deposit`. Deposit success response: `user`, `tariff`, `paid: true`. |

### User devices, payments, and transactions

| Method and URL | Arguments and fields | Behavior |
| --- | --- | --- |
| `POST /reseller/api/users/devices` | Optional path page: `/reseller/api/users/devices/{page}`. Optional query filters: `find[id]`, `find[user]`, `find[email]`, `find[ip]`, `find[country]`, `find[duid]`, `find[platform]`, `find[status]`, `find[online]`. Optional sort: `by` accepts `id` or `last`; `order` accepts `ASC` or `DESC`. | Returns reseller users' devices. Default order is `last` descending. |
| `POST /reseller/api/users/devices/edit/{id}` | Path: device `id`. Required POST field: `status`, either `0` or `1`. | Updates device status. Success response: `device`, `updated: true`. |
| `POST /reseller/api/users/payments` | Optional path page: `/reseller/api/users/payments/{page}`. Optional query filters: `find[id]`, `find[user]`, `find[email]`, `find[details]`. Optional sort: `by` accepts `id` or `time`; `order` accepts `ASC` or `DESC`. | Returns payments belonging to the reseller's users. |
| `POST /reseller/api/users/transactions` | Optional path page: `/reseller/api/users/transactions/{page}`. Optional query filters: `find[id]`, `find[user]`, `find[email]`, `find[type]`, `find[comment]`. Optional sort: `by` accepts `id` or `time`; `order` accepts `ASC` or `DESC`. | Returns transactions belonging to the reseller's users. |

### TV and VOD playlists

| Method and URL | Arguments and fields | Behavior |
| --- | --- | --- |
| `POST /reseller/api/users/tv-playlists` | Optional path page: `/reseller/api/users/tv-playlists/{page}`. Optional query filters: `find[id]`, `find[user]`, `find[email]`, `find[url]`, `find[epg]`. Optional sort: `by` accepts `id`, `created`, `modified`, or `updated`; `order` accepts `ASC` or `DESC`. | Returns TV playlists. Default order is `updated` descending. |
| `POST /reseller/api/users/tv-playlists/add` | Optional path user ID: `/reseller/api/users/tv-playlists/add/{user}`. Without it, required POST field `email` identifies the user. POST fields: `url` (string), `epg` (string), and/or uploaded file `m3u`. | Adds the user's TV playlist. Send the full existing user email when identifying the user by POST. At least one playlist source must be supplied. Use multipart form data for `m3u`. Success response: `playlist`, `created: true`. |
| `POST /reseller/api/users/tv-playlists/edit/{id}` | Path: TV playlist `id`. Optional POST fields: `url`, `epg` (strings), uploaded file `m3u`, or `delete=m3u` to remove the uploaded M3U file. | Updates a TV playlist. Use multipart form data for `m3u`. Success response: `playlist`, `updated: true`. |
| `POST /reseller/api/users/tv-playlists/user/{id}` | Path: user `id`. | Returns that user's TV playlist, or `null` when no playlist exists. The user must belong to the reseller. |
| `POST /reseller/api/users/tv-playlists/delete/{id}` | Path: TV playlist `id`. | Deletes the playlist. Success response: `playlist`, `deleted: true`. |
| `POST /reseller/api/users/vod-playlists` | Optional path page: `/reseller/api/users/vod-playlists/{page}`. Optional query filters: `find[id]`, `find[user]`, `find[email]`, `find[url]`. Optional sort: `by` accepts `id`, `created`, `modified`, or `updated`; `order` accepts `ASC` or `DESC`. | Returns VOD playlists. Default order is `updated` descending. |
| `POST /reseller/api/users/vod-playlists/add` | Optional path user ID: `/reseller/api/users/vod-playlists/add/{user}`. Without it, required POST field `email` identifies the user. POST fields: `url` (string) and/or uploaded file `m3u`. | Adds the user's VOD playlist. Send the full existing user email when identifying the user by POST. At least one playlist source must be supplied. Use multipart form data for `m3u`. Success response: `playlist`, `created: true`. |
| `POST /reseller/api/users/vod-playlists/edit/{id}` | Path: VOD playlist `id`. Optional POST field: `url` (string), uploaded file `m3u`, or `delete=m3u` to remove the uploaded M3U file. | Updates a VOD playlist. Use multipart form data for `m3u`. Success response: `playlist`, `updated: true`. |
| `POST /reseller/api/users/vod-playlists/user/{id}` | Path: user `id`. | Returns that user's VOD playlist, or `null` when no playlist exists. The user must belong to the reseller. |
| `POST /reseller/api/users/vod-playlists/delete/{id}` | Path: VOD playlist `id`. | Deletes the playlist. Success response: `playlist`, `deleted: true`. |
| `POST /reseller/api/users/playlists/dns/edit` | Required POST fields: `old` and `new` (hostnames). | Replaces the old host with the new host in the reseller's TV playlist URL/EPG and VOD playlist URL fields. The new hostname must resolve. |

### Reseller payments and transactions

| Method and URL | Arguments and fields | Behavior |
| --- | --- | --- |
| `POST /reseller/api/payments` | Optional path page: `/reseller/api/payments/{page}`. Optional query filters: `find[id]`, `find[details]`. | Returns reseller payments, newest ID first. |
| `POST /reseller/api/transactions` | Optional path page: `/reseller/api/transactions/{page}`. Optional query filters: `find[id]`, `find[type]`, `find[comment]`. | Returns reseller transactions, newest ID first. |
| `POST /reseller/api/refill/vias` | No additional fields. | Returns the payment methods currently available to the reseller. |
| `POST /reseller/api/refill/max` | No additional fields. | Returns the maximum allowed refill amount as a JSON number. |
| `POST /reseller/api/refill` | Required POST fields: `sum` (integer from 10 through the maximum, in increments of 10) and `via` (a value returned by `POST /reseller/api/refill/vias`). For external providers, also provide `success` and `failed` absolute HTTP(S) URLs; optional `cancel` URL defaults to `failed`. | Starts a reseller deposit payment. The maximum amount depends on the reseller's purchase history; get it with `POST /reseller/api/refill/max`. |

### Payment fields and provider responses

Available external payment methods depend on the country. Retrieve them with `POST /reseller/api/refill/vias` and use a returned value for `via`. For user refills, `deposit` is a special value that charges the reseller's balance and is not included in that list. Reseller deposit refills require an external payment provider. The maximum reseller refill amount depends on purchase history and is returned by `POST /reseller/api/refill/max`; the minimum is 10 and amounts must be in increments of 10.

Payment return fields are URL strings:

| Field | Required | Description |
| --- | --- | --- |
| `success` | Yes for external providers | URL to return to after successful payment. |
| `failed` | Yes for external providers | URL to return to after failed payment. |
| `cancel` | No | Cancellation URL; defaults to `failed`. |

Payment responses include `type` and the corresponding checkout data: `redirect` includes `url`, while `html` includes provider `html`.

## Response examples

The values below are illustrative. Successful responses use HTTP `200`; errors include an `error` field. List endpoints return `items` and `paging`.

### Error response

```json
{
  "error": "Wrong arguments"
}
```

### Available refill methods

`POST /reseller/api/refill/vias` returns a JSON array:

```json
[
  "paypal",
  "stripe"
]
```

### Maximum refill amount

`POST /reseller/api/refill/max` returns a JSON number:

```json
50
```

### List users

`POST /reseller/api/users/{page}`

```json
{
  "items": [
    {
      "id": 123,
      "email": "alice@example.com",
      "password": "example-password",
      "status": 1,
      "state": "active",
      "tariff": 5,
      "plan": "Monthly 3",
      "period": "monthly",
      "trial": 0,
      "start": 1794000000,
      "end": 1796601600,
      "expires": 2592000,
      "deposit": 0,
      "rest": 0,
      "devices": 1,
      "pin": "1234",
      "timezone": "UTC",
      "notes": null,
      "created": 1793990000
    }
  ],
  "paging": {
    "page": 1,
    "limit": 25,
    "total": 1,
    "pages": 1
  }
}
```

### List TV playlists

`POST /reseller/api/users/tv-playlists/{page}`

```json
{
  "items": [
    {
      "id": 789,
      "user": 123,
      "email": "alice@example.com",
      "m3u": null,
      "url": "https://example.com/playlist.m3u",
      "epg": "https://example.com/epg.xml",
      "count": 42,
      "size": 8192,
      "created": 1793990000,
      "modified": 1793990000,
      "updated": 1794000000
    }
  ],
  "paging": {
    "page": 1,
    "limit": 25,
    "total": 1,
    "pages": 1
  }
}
```

### Create user

`POST /reseller/api/users/add` returns:

```json
{
  "user": 123,
  "email": "alice@example.com",
  "password": "ChangeMe123",
  "created": true
}
```

### Update user

`POST /reseller/api/users/edit/{id}` returns:

```json
{
  "user": 123,
  "updated": true
}
```

### Delete user

`POST /reseller/api/users/delete/{id}` returns:

```json
{
  "user": 123,
  "deleted": true
}
```

### Activate tariff

`POST /reseller/api/users/activate/{id}/{tariff}` returns:

```json
{
  "user": 123,
  "tariff": 5,
  "activated": true
}
```

### Renew user subscription

`POST /reseller/api/users/renew/{id}` returns:

```json
{
  "user": 123,
  "renewed": true
}
```

### Cancel user subscription

`POST /reseller/api/users/cancel/{id}` returns:

```json
{
  "user": 123,
  "cancelled": true
}
```

### Update device

`POST /reseller/api/users/devices/edit/{id}` returns:

```json
{
  "device": 456,
  "updated": true
}
```

### Add playlist

`POST /reseller/api/users/tv-playlists/add` or `/reseller/api/users/vod-playlists/add` returns:

```json
{
  "playlist": 789,
  "created": true
}
```

### Edit playlist

`POST /reseller/api/users/tv-playlists/edit/{id}` or `/reseller/api/users/vod-playlists/edit/{id}` returns:

```json
{
  "playlist": 789,
  "updated": true
}
```

### Delete playlist

`POST /reseller/api/users/tv-playlists/delete/{id}` or `/reseller/api/users/vod-playlists/delete/{id}` returns:

```json
{
  "playlist": 789,
  "deleted": true
}
```

### Refill user from reseller deposit

`POST /reseller/api/users/refill/{id}/{tariff}` with `via=deposit` returns:

```json
{
  "user": 123,
  "tariff": 5,
  "paid": true
}
```

### External payment response

External payment requests return either a redirect URL or HTML to display, depending on the payment method. `POST /reseller/api/refill` redirect example:

```json
{
  "type": "redirect",
  "url": "https://payments.example.com/checkout/session"
}
```

## Copyable request examples

All examples use POST form fields, not a JSON request body. Put the token in the POST body and filters or sorting options in the URL query string. Replace the token placeholder with your own. PHP snippets require the cURL extension; Python snippets use `requests` (`python -m pip install requests`). Each endpoint example uses the shared helpers below.

### Shared setup

**cURL**

```bash
BASE="https://console.clouddy.online/reseller/api"
TOKEN="<reseller-token>"
USER_ID="<user-id>"
TARIFF_ID="<tariff-id>"
DEVICE_ID="<device-id>"
PLAYLIST_ID="<playlist-id>"
PAGE="1"
```

**PHP**

```php
<?php
const API_BASE = "https://console.clouddy.online/reseller/api";
const API_TOKEN = "<reseller-token>";
$USER_ID = "<user-id>";
$TARIFF_ID = "<tariff-id>";
$DEVICE_ID = "<device-id>";
$PLAYLIST_ID = "<playlist-id>";

function api($path, $fields = array(), $query = array())
{
    $url = rtrim(API_BASE, "/")."/".ltrim($path, "/");
    if ( $query )
    {
        $url .= "?".http_build_query($query);
    }
    $fields["token"] = API_TOKEN;
    $curl = curl_init($url);
    curl_setopt_array($curl, array
    (
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $fields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 90,
    ));
    $body = curl_exec($curl);
    if ( $body === false )
    {
        throw new RuntimeException(curl_error($curl));
    }
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $result = array("status" => $status, "data" => json_decode($body, true));
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    return $result;
}
```

For a file upload, put a `CURLFile` in `$fields`, as shown in the M3U upload examples below.

**Python**

```python
import requests

API_BASE = "https://console.clouddy.online/reseller/api"
API_TOKEN = "<reseller-token>"
USER_ID = "<user-id>"
TARIFF_ID = "<tariff-id>"
DEVICE_ID = "<device-id>"
PLAYLIST_ID = "<playlist-id>"

def api(path, fields=None, query=None, files=None):
    data = dict(fields or {})
    data["token"] = API_TOKEN
    response = requests.post(
        API_BASE.rstrip("/") + "/" + path.lstrip("/"),
        data=data,
        params=query,
        files=files,
        timeout=90,
    )
    try:
        body = response.json()
    except ValueError:
        body = response.text
    result = {"status": response.status_code, "data": body}
    print(result)
    return result
```

### Reseller info

`POST /reseller/api/info`

**cURL**

```bash
curl -X POST "$BASE/info" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("info");
```

**Python**

```python
api("info")
```

### Edit reseller profile

`POST /reseller/api/profile/edit`

**cURL**

```bash
curl -X POST "$BASE/profile/edit" \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode "company=Example Ltd"
```

**PHP**

```php
api("profile/edit", array("company" => "Example Ltd"));
```

**Python**

```python
api("profile/edit", {"company": "Example Ltd"})
```

### List active users (page 1, ID descending)

`POST /reseller/api/users/{page}`

**cURL**

```bash
curl -X POST "$BASE/users/$PAGE?find%5Bstate%5D=active&by=id&order=DESC" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/1", array(), array("find[state]" => "active", "by" => "id", "order" => "DESC"));
```

**Python**

```python
api("users/1", query={"find[state]": "active", "by": "id", "order": "DESC"})
```

### Create user

`POST /reseller/api/users/add`

**cURL**

```bash
curl -X POST "$BASE/users/add" \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode "login=alice" \
  --data-urlencode "password=ChangeMe123" \
  --data-urlencode "pin=1234" \
  --data-urlencode "notes=API example"
```

**PHP**

```php
api("users/add", array
(
    "login"    => "alice",
    "password" => "ChangeMe123",
    "pin"      => "1234",
    "notes"    => "API example",
));
```

**Python**

```python
api("users/add", {"login": "alice", "password": "ChangeMe123", "pin": "1234", "notes": "API example"})
```

Use the returned `user` value as the user ID in later paths, such as `/reseller/api/users/tariffs/123` or `/reseller/api/users/refill/123/{tariff}`. The tariffs endpoint returns the tariff IDs available for that user.

### Edit user

`POST /reseller/api/users/edit/{id}`

**cURL**

```bash
curl -X POST "$BASE/users/edit/$USER_ID" \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode "notes=Updated by API"
```

**PHP**

```php
api("users/edit/$USER_ID", array("notes" => "Updated by API"));
```

**Python**

```python
api(f"users/edit/{USER_ID}", {"notes": "Updated by API"})
```

### Delete empty trial user

`POST /reseller/api/users/delete/{id}`

**cURL**

```bash
curl -X POST "$BASE/users/delete/$USER_ID" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/delete/$USER_ID");
```

**Python**

```python
api(f"users/delete/{USER_ID}")
```

### Get user's tariffs

`POST /reseller/api/users/tariffs/{id}`

**cURL**

```bash
curl -X POST "$BASE/users/tariffs/$USER_ID" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/tariffs/$USER_ID");
```

**Python**

```python
api(f"users/tariffs/{USER_ID}")
```

### Renew user subscription

`POST /reseller/api/users/renew/{id}`

**cURL**

```bash
curl -X POST "$BASE/users/renew/$USER_ID" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/renew/$USER_ID");
```

**Python**

```python
api(f"users/renew/{USER_ID}")
```

### Cancel user subscription

`POST /reseller/api/users/cancel/{id}`

**cURL**

```bash
curl -X POST "$BASE/users/cancel/$USER_ID" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/cancel/$USER_ID");
```

**Python**

```python
api(f"users/cancel/{USER_ID}")
```

### Activate tariff

`POST /reseller/api/users/activate/{id}/{tariff}`

**cURL**

```bash
curl -X POST "$BASE/users/activate/$USER_ID/$TARIFF_ID" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/activate/$USER_ID/$TARIFF_ID");
```

**Python**

```python
api(f"users/activate/{USER_ID}/{TARIFF_ID}")
```

### Refill user via reseller deposit

`POST /reseller/api/users/refill/{id}/{tariff}`

For this example, `via=deposit` charges the reseller's balance. This special value is not returned by `/reseller/api/refill/vias`.

**cURL**

```bash
curl -X POST "$BASE/users/refill/$USER_ID/$TARIFF_ID" \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode "via=deposit"
```

**PHP**

```php
api("users/refill/$USER_ID/$TARIFF_ID", array("via" => "deposit"));
```

**Python**

```python
api(f"users/refill/{USER_ID}/{TARIFF_ID}", {"via": "deposit"})
```

### Refill user via Stripe

`POST /reseller/api/users/refill/{id}/{tariff}`

For an external provider, set `via` to a value returned by `POST /reseller/api/refill/vias`. Stripe is used in this example.

**cURL**

```bash
curl -X POST "$BASE/users/refill/$USER_ID/$TARIFF_ID" \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode "via=stripe" \
  --data-urlencode "success=https://example.com/success" \
  --data-urlencode "failed=https://example.com/failed" \
  --data-urlencode "cancel=https://example.com/cancel"
```

**PHP**

```php
api("users/refill/$USER_ID/$TARIFF_ID", array
(
    "via"     => "stripe",
    "success" => "https://example.com/success",
    "failed"  => "https://example.com/failed",
    "cancel"  => "https://example.com/cancel",
));
```

**Python**

```python
api(f"users/refill/{USER_ID}/{TARIFF_ID}", {"via": "stripe", "success": "https://example.com/success", "failed": "https://example.com/failed", "cancel": "https://example.com/cancel"})
```

### List user devices

`POST /reseller/api/users/devices/{page}`

**cURL**

```bash
curl -X POST "$BASE/users/devices/$PAGE?find%5Buser%5D=$USER_ID&by=last&order=DESC" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/devices/1", array(), array("find[user]" => $USER_ID, "by" => "last", "order" => "DESC"));
```

**Python**

```python
api("users/devices/1", query={"find[user]": USER_ID, "by": "last", "order": "DESC"})
```

### Edit device status

`POST /reseller/api/users/devices/edit/{id}`

**cURL**

```bash
curl -X POST "$BASE/users/devices/edit/$DEVICE_ID" \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode "status=1"
```

**PHP**

```php
api("users/devices/edit/$DEVICE_ID", array("status" => "1"));
```

**Python**

```python
api(f"users/devices/edit/{DEVICE_ID}", {"status": "1"})
```

### List user payments

`POST /reseller/api/users/payments/{page}`

**cURL**

```bash
curl -X POST "$BASE/users/payments/$PAGE?find%5Buser%5D=$USER_ID&by=time&order=DESC" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/payments/1", array(), array("find[user]" => $USER_ID, "by" => "time", "order" => "DESC"));
```

**Python**

```python
api("users/payments/1", query={"find[user]": USER_ID, "by": "time", "order": "DESC"})
```

### List user transactions

`POST /reseller/api/users/transactions/{page}`

**cURL**

```bash
curl -X POST "$BASE/users/transactions/$PAGE?find%5Buser%5D=$USER_ID&by=time&order=DESC" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/transactions/1", array(), array("find[user]" => $USER_ID, "by" => "time", "order" => "DESC"));
```

**Python**

```python
api("users/transactions/1", query={"find[user]": USER_ID, "by": "time", "order": "DESC"})
```

### List TV playlists

`POST /reseller/api/users/tv-playlists/{page}`

**cURL**

```bash
curl -X POST "$BASE/users/tv-playlists/$PAGE?find%5Buser%5D=$USER_ID&by=updated&order=DESC" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/tv-playlists/1", array(), array
(
    "find[user]" => $USER_ID,
    "by"         => "updated",
    "order"      => "DESC",
));
```

**Python**

```python
api("users/tv-playlists/1", query={"find[user]": USER_ID, "by": "updated", "order": "DESC"})
```

### Add TV playlist by user ID

`POST /reseller/api/users/tv-playlists/add/{user}`

**cURL**

```bash
curl -X POST "$BASE/users/tv-playlists/add/$USER_ID" \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode "url=https://example.com/tv.m3u" \
  --data-urlencode "epg=https://example.com/guide.xml"
```

**PHP**

```php
api("users/tv-playlists/add/$USER_ID", array
(
    "url" => "https://example.com/tv.m3u",
    "epg" => "https://example.com/guide.xml",
));
```

**Python**

```python
api(f"users/tv-playlists/add/{USER_ID}", {"url": "https://example.com/tv.m3u", "epg": "https://example.com/guide.xml"})
```

### Edit TV playlist

`POST /reseller/api/users/tv-playlists/edit/{id}`

**cURL**

```bash
curl -X POST "$BASE/users/tv-playlists/edit/$PLAYLIST_ID" \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode "url=https://example.com/new-tv.m3u"
```

**PHP**

```php
api("users/tv-playlists/edit/$PLAYLIST_ID", array("url" => "https://example.com/new-tv.m3u"));
```

**Python**

```python
api(f"users/tv-playlists/edit/{PLAYLIST_ID}", {"url": "https://example.com/new-tv.m3u"})
```

### Get TV playlist by user ID

`POST /reseller/api/users/tv-playlists/user/{id}`

**cURL**

```bash
curl -X POST "$BASE/users/tv-playlists/user/$USER_ID" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/tv-playlists/user/$USER_ID");
```

**Python**

```python
api(f"users/tv-playlists/user/{USER_ID}")
```

### Delete TV playlist

`POST /reseller/api/users/tv-playlists/delete/{id}`

**cURL**

```bash
curl -X POST "$BASE/users/tv-playlists/delete/$PLAYLIST_ID" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/tv-playlists/delete/$PLAYLIST_ID");
```

**Python**

```python
api(f"users/tv-playlists/delete/{PLAYLIST_ID}")
```

### List VOD playlists

`POST /reseller/api/users/vod-playlists/{page}`

**cURL**

```bash
curl -X POST "$BASE/users/vod-playlists/$PAGE?find%5Buser%5D=$USER_ID&by=updated&order=DESC" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/vod-playlists/1", array(), array
(
    "find[user]" => $USER_ID,
    "by"         => "updated",
    "order"      => "DESC",
));
```

**Python**

```python
api("users/vod-playlists/1", query={"find[user]": USER_ID, "by": "updated", "order": "DESC"})
```

### Add VOD playlist by user ID

`POST /reseller/api/users/vod-playlists/add/{user}`

**cURL**

```bash
curl -X POST "$BASE/users/vod-playlists/add/$USER_ID" \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode "url=https://example.com/vod.m3u"
```

**PHP**

```php
api("users/vod-playlists/add/$USER_ID", array("url" => "https://example.com/vod.m3u"));
```

**Python**

```python
api(f"users/vod-playlists/add/{USER_ID}", {"url": "https://example.com/vod.m3u"})
```

### Edit VOD playlist

`POST /reseller/api/users/vod-playlists/edit/{id}`

**cURL**

```bash
curl -X POST "$BASE/users/vod-playlists/edit/$PLAYLIST_ID" \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode "url=https://example.com/new-vod.m3u"
```

**PHP**

```php
api("users/vod-playlists/edit/$PLAYLIST_ID", array("url" => "https://example.com/new-vod.m3u"));
```

**Python**

```python
api(f"users/vod-playlists/edit/{PLAYLIST_ID}", {"url": "https://example.com/new-vod.m3u"})
```

### Get VOD playlist by user ID

`POST /reseller/api/users/vod-playlists/user/{id}`

**cURL**

```bash
curl -X POST "$BASE/users/vod-playlists/user/$USER_ID" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/vod-playlists/user/$USER_ID");
```

**Python**

```python
api(f"users/vod-playlists/user/{USER_ID}")
```

### Delete VOD playlist

`POST /reseller/api/users/vod-playlists/delete/{id}`

**cURL**

```bash
curl -X POST "$BASE/users/vod-playlists/delete/$PLAYLIST_ID" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("users/vod-playlists/delete/$PLAYLIST_ID");
```

**Python**

```python
api(f"users/vod-playlists/delete/{PLAYLIST_ID}")
```

### Replace playlist DNS host

`POST /reseller/api/users/playlists/dns/edit`

**cURL**

```bash
curl -X POST "$BASE/users/playlists/dns/edit" \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode "old=old.example.com" \
  --data-urlencode "new=example.com"
```

**PHP**

```php
api("users/playlists/dns/edit", array("old" => "old.example.com", "new" => "example.com"));
```

**Python**

```python
api("users/playlists/dns/edit", {"old": "old.example.com", "new": "example.com"})
```

### List reseller payments

`POST /reseller/api/payments/{page}`

**cURL**

```bash
curl -X POST "$BASE/payments/$PAGE?by=id&order=DESC" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("payments/1", array(), array("by" => "id", "order" => "DESC"));
```

**Python**

```python
api("payments/1", query={"by": "id", "order": "DESC"})
```

### List reseller transactions

`POST /reseller/api/transactions/{page}`

**cURL**

```bash
curl -X POST "$BASE/transactions/$PAGE?by=id&order=DESC" \
  --data-urlencode "token=$TOKEN"
```

**PHP**

```php
api("transactions/1", array(), array("by" => "id", "order" => "DESC"));
```

**Python**

```python
api("transactions/1", query={"by": "id", "order": "DESC"})
```

### Refill reseller deposit via Stripe

`POST /reseller/api/refill`

Set `via` to a value returned by `POST /reseller/api/refill/vias`. Stripe is used in this example.

**cURL**

```bash
curl -X POST "$BASE/refill" \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode "sum=10" \
  --data-urlencode "via=stripe" \
  --data-urlencode "success=https://example.com/success" \
  --data-urlencode "failed=https://example.com/failed" \
  --data-urlencode "cancel=https://example.com/cancel"
```

**PHP**

```php
api("refill", array
(
    "sum"     => "10",
    "via"     => "stripe",
    "success" => "https://example.com/success",
    "failed"  => "https://example.com/failed",
    "cancel"  => "https://example.com/cancel",
));
```

**Python**

```python
api("refill", {"sum": "10", "via": "stripe", "success": "https://example.com/success", "failed": "https://example.com/failed", "cancel": "https://example.com/cancel"})
```

### Uploading an M3U file

Use a real local M3U file. Do not include `url` when testing the uploaded-file variant if you want the playlist to store the file without a URL.

**cURL**

```bash
curl -X POST "$BASE/users/tv-playlists/add/$USER_ID" \
  --form "token=$TOKEN" \
  --form "m3u=@/path/to/playlist.m3u;type=audio/x-mpegurl"
```

For VOD, replace `tv-playlists` with `vod-playlists`. To edit an existing playlist, use `/reseller/api/users/tv-playlists/edit/{id}` or `/reseller/api/users/vod-playlists/edit/{id}`.

**PHP**

```php
api("users/tv-playlists/add/$USER_ID", array
(
    "m3u" => new CURLFile("/path/to/playlist.m3u", "audio/x-mpegurl", "playlist.m3u"),
));
```

Use `vod-playlists` in the path for VOD, and use the corresponding `/edit/{playlist-id}` path to upload a replacement file.

**Python**

```python
with open("/path/to/playlist.m3u", "rb") as playlist:
    api(f"users/tv-playlists/add/{USER_ID}", files={"m3u": ("playlist.m3u", playlist, "audio/x-mpegurl")})
```

Use `vod-playlists` in the path for VOD, and use the corresponding `/edit/{id}` path to upload a replacement file.

### Notes for running the examples

- The create-user example uses the login alice; choose a unique login for each run.
- Deletion examples perform real deletions. The user-delete endpoint only accepts an empty trial user.
- The refill examples create a payment session or charge the reseller deposit according to via. Stripe sessions return a checkout URL; completing checkout is a separate action.
- Playlist add examples create records. Replace example playlist URLs with valid sources you control.
- Query filters belong in the URL query string, not the POST body.

## Filter values

Filters are query-string fields inside `find`. For example, `find[state]=active`. Only the fields listed for each endpoint are accepted.

## Notes

- IDs and page numbers are URL path arguments where shown; do not send those values again in POST.
- All operations are scoped to the authenticated reseller.
- The API determines the amount for user tariff payments. Reseller deposit refill requires a sum field.
- Payment return URLs must be absolute http or https URLs.
- Playlist uploads require a real multipart file field named m3u.
