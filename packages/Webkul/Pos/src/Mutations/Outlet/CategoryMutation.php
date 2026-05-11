<?php

namespace Webkul\Pos\Mutations\Outlet;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Helpers\RoleGate;

/**
 * Category CRUD for the merchant-management surface. Manager+owner only.
 *
 * Reuses the core Bagisto CategoryRepository so the same channel scoping,
 * translation handling, and event dispatch that drives the storefront also
 * applies to POS-side category management. A new `position` defaults to
 * (max(position)+1) of the current root level so drag-reorder is purely
 * a position-update operation.
 */
class CategoryMutation
{
    public function __construct(
        protected CategoryRepository $categoryRepository,
    ) {}

    public function create($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $validator = Validator::make($args, [
            'name'        => 'required|string|max:190',
            'slug'        => 'nullable|string|max:120',
            'description' => 'nullable|string|max:5000',
            'parentId'    => 'nullable|integer',
            'status'      => 'nullable|boolean',
            'position'    => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $defaultChannel = core()->getDefaultChannel();
        $locale = core()->getRequestedLocaleCode();

        // Default parent = root for the current channel.
        $parentId = $args['parentId'] ?? optional($defaultChannel)->root_category_id;
        $slug = $args['slug'] ?? Str::slug($args['name']);
        if ($slug === '') {
            $slug = 'category-' . time();
        }

        // Auto-position: append to end of siblings.
        $position = $args['position'] ?? null;
        if ($position === null) {
            $position = ((int) DB::table('categories')
                ->where('parent_id', $parentId)
                ->max('position')) + 1;
        }

        $payload = [
            'locale'    => $locale,
            'parent_id' => $parentId,
            'status'    => $args['status'] ?? 1,
            'position'  => $position,
            $locale => [
                'name'        => $args['name'],
                'slug'        => $slug,
                'description' => $args['description'] ?? '',
                'meta_title'  => $args['name'],
                'url_path'    => $slug,
            ],
        ];

        $category = $this->categoryRepository->create($payload);

        return [
            'success'  => true,
            'message'  => trans('pos::app.outlet.category.created'),
            'category' => $this->serialize($category, $locale),
        ];
    }

    public function update($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $validator = Validator::make($args, [
            'id'          => 'required|integer',
            'name'        => 'sometimes|required|string|max:190',
            'slug'        => 'sometimes|nullable|string|max:120',
            'description' => 'sometimes|nullable|string|max:5000',
            'parentId'    => 'sometimes|nullable|integer',
            'status'      => 'sometimes|nullable|boolean',
            'position'    => 'sometimes|nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $category = $this->categoryRepository->find($args['id']);
        if (! $category) {
            throw new CustomException(trans('pos::app.outlet.category.not-found'));
        }

        $locale = core()->getRequestedLocaleCode();

        // CategoryRepository::update() calls setSameAttributeValueToAllLocale($data, 'slug'),
        // which array-accesses $data['slug'] without an isset guard. Always
        // provide one (preserving the current slug if the caller didn't send one)
        // so the helper doesn't crash on partial updates.
        $existingSlug = DB::table('category_translations')
            ->where('category_id', $args['id'])
            ->where('locale', $locale)
            ->value('slug') ?: ('category-' . $args['id']);

        $data = [
            'locale' => $locale,
            'slug'   => $args['slug'] ?? $existingSlug,
        ];

        foreach (['parentId' => 'parent_id', 'status' => 'status', 'position' => 'position'] as $in => $out) {
            if (array_key_exists($in, $args) && $args[$in] !== null) {
                $data[$out] = $args[$in];
            }
        }

        $localePayload = [
            'slug'     => $data['slug'],
            'url_path' => $data['slug'],
        ];
        if (array_key_exists('name', $args)) {
            $localePayload['name'] = $args['name'];
        }
        if (array_key_exists('description', $args)) {
            $localePayload['description'] = $args['description'];
        }
        $data[$locale] = $localePayload;

        $category = $this->categoryRepository->update($data, $args['id']);

        return [
            'success'  => true,
            'message'  => trans('pos::app.outlet.category.updated'),
            'category' => $this->serialize($category, $locale),
        ];
    }

    public function delete($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $validator = Validator::make($args, [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        $category = $this->categoryRepository->find($args['id']);
        if (! $category) {
            throw new CustomException(trans('pos::app.outlet.category.not-found'));
        }

        $hasChildren = DB::table('categories')->where('parent_id', $args['id'])->exists();
        if ($hasChildren) {
            throw new CustomException(trans('pos::app.outlet.category.has-children'));
        }

        $this->categoryRepository->delete($args['id']);

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.category.deleted'),
        ];
    }

    /**
     * Drag-reorder helper. Accepts a flat list of {id, position} pairs.
     * Wraps the bulk update in a transaction so a half-applied reorder
     * never leaves the tree out of order.
     */
    public function reorder($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireManager($context->user());

        $validator = Validator::make($args, [
            'items'             => 'required|array|min:1',
            'items.*.id'        => 'required|integer',
            'items.*.position'  => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        DB::transaction(function () use ($args) {
            foreach ($args['items'] as $row) {
                DB::table('categories')
                    ->where('id', $row['id'])
                    ->update(['position' => $row['position'], 'updated_at' => now()]);
            }
        });

        return [
            'success' => true,
            'message' => trans('pos::app.outlet.category.updated'),
            'count'   => count($args['items']),
        ];
    }

    protected function serialize($category, string $locale): array
    {
        $tr = DB::table('category_translations')
            ->where('category_id', $category->id)
            ->where('locale', $locale)
            ->first();

        return [
            'id'          => $category->id,
            'name'        => $tr->name ?? '',
            'slug'        => $tr->slug ?? '',
            'description' => $tr->description ?? '',
            'parentId'    => $category->parent_id,
            'status'      => (bool) $category->status,
            'position'    => (int) $category->position,
        ];
    }
}
