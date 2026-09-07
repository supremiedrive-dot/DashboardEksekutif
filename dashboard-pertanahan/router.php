<?php
// Development server protection; Apache uses .htaccess.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$path = str_replace('\\', '/', $path);
if (preg_match('~(?:^|/)(?:\.|data(?:/|$)|database(?:/|$)|config(?:/|$)|services(?:/|$)|tests(?:/|$)|docs(?:/|$)|tmp(?:/|$)|temp(?:/|$))|\.(?:sql|sqlite|db|bak)$~i', $path)) {
    http_response_code(403);
    exit('Forbidden');
}
return false;
