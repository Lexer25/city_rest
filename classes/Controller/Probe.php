<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Probe extends Controller
{
    public function action_index()
    {
        $this->response->headers('Content-Type', 'text/plain; charset=utf-8');

        $uri = 'api/v1/version';

        $out = "=== Match directly: '{$uri}' ===\n";

        $matched_route = null;
        $matched_params = null;

        foreach (Route::all() as $name => $route) {
            // Пробуем сматчить роут с текущим URI
            $params = $route->matches(Request::factory($uri));

            if ($params === false) {
                continue;
            }

            $out .= "  FIRST MATCH: {$name}\n";
            $out .= "    uri:        " . $route->uri(array()) . "\n";
            $out .= "    defaults:   " . json_encode($route->defaults(), JSON_UNESCAPED_UNICODE) . "\n";
            $out .= "    params:     " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n";

            $matched_route = $name;
            $matched_params = $params;
            break;
        }

        if ($matched_route === null) {
            $out .= "  NO MATCH\n";
        }

        $out .= "\n=== Test each rest_* route separately ===\n";

        foreach (array('rest_auth', 'rest_version') as $rname) {
            $route = Route::get($rname);
            if ($route === null) {
                $out .= "  {$rname}: NOT REGISTERED\n";
                continue;
            }
            $out .= sprintf("  %-15s uri=%s defaults=%s\n",
                $rname,
                $route->uri(array()),
                json_encode($route->defaults(), JSON_UNESCAPED_UNICODE)
            );

            try {
                $params = $route->matches(Request::factory($uri));
                if ($params === false) {
                    $out .= "                  -> matches: NO\n";
                } else {
                    $out .= "                  -> matches: YES, params=" . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n";
                }
            } catch (Exception $e) {
                $out .= "                  -> EXCEPTION: " . $e->getMessage() . "\n";
            }
        }
		$out .= "\n=== Controller lookup ===\n";
$lookups = array(
    'controller/rest/version',
    'Controller/Rest/Version',
);
foreach ($lookups as $path) {
    $found = Kohana::find_file('classes', $path);
    $out .= sprintf("  find_file('%s') = %s\n", $path, $found ? $found : 'FALSE');
}

$out .= "\n=== class_exists ===\n";
foreach (array('Controller_Rest_Version', 'Rest_Jwt', 'Rest_Response', 'Rest_Error') as $cls) {
    $out .= sprintf("  %-30s %s\n", $cls, class_exists($cls) ? 'OK' : 'MISSING');
}
        $this->response->body($out);
    }
}