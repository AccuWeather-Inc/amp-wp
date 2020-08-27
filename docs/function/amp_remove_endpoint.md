## Function `amp_remove_endpoint`

```php
function amp_remove_endpoint( $url );
```

Remove the AMP endpoint (and query var) from a given URL.

### Arguments

* `string $url` - URL.

### Return value

`string` - URL with AMP stripped.

### Source

:link: [includes/amp-helper-functions.php:741](../../includes/amp-helper-functions.php#L741-L750)

<details>
<summary>Show Code</summary>

```php
function amp_remove_endpoint( $url ) {

	// Strip endpoint.
	$url = preg_replace( ':/' . preg_quote( amp_get_slug(), ':' ) . '(?=/?(\?|#|$)):', '', $url );

	// Strip query var.
	$url = remove_query_arg( amp_get_slug(), $url );

	return $url;
}
```

</details>
