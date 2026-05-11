<?php

namespace Webkul\Pos\Queries\Outlet;

use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * Category list for the POS merchant-management surface. Returns rows in
 * `position ASC` order, locale-translated, scoped to either the whole tree
 * or one parent's children.
 */
class CategoryQuery
{
    public function list($root, array $args, GraphQLContext $context): array
    {
        $locale   = core()->getRequestedLocaleCode();
        $parentId = $args['parentId'] ?? null;

        $q = DB::table('categories AS c')
            ->leftJoin('category_translations AS t', function ($j) use ($locale) {
                $j->on('t.category_id', '=', 'c.id')->where('t.locale', $locale);
            })
            ->orderBy('c.position', 'ASC')
            ->orderBy('c.id', 'ASC')
            ->select(
                'c.id',
                't.name as name',
                't.slug as slug',
                't.description as description',
                'c.parent_id as parentId',
                'c.status as status',
                'c.position as position'
            );

        if ($parentId !== null) {
            $q->where('c.parent_id', $parentId);
        }

        return $q->get()->map(fn ($r) => [
            'id'          => (int) $r->id,
            'name'        => (string) ($r->name ?? ''),
            'slug'        => $r->slug,
            'description' => $r->description,
            'parentId'    => $r->parentId,
            'status'      => (bool) $r->status,
            'position'    => (int) $r->position,
        ])->all();
    }
}
