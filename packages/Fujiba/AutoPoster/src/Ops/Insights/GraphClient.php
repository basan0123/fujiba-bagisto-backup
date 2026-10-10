<?php

namespace Fujiba\AutoPoster\Ops\Insights;

/** GET-only Graph API access. There is intentionally no post/delete method. */
interface GraphClient
{
    /** @return array decoded JSON body; throws GraphException on API error */
    public function get(string $path, array $query, string $token): array;
}
