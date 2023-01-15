[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner-direct-single.svg)](https://stand-with-ukraine.pp.ua)

# Laravel Tree

[![PHPUnit](https://img.shields.io/github/actions/workflow/status/nevadskiy/laravel-tree/phpunit.yml?branch=master)](https://packagist.org/packages/nevadskiy/laravel-tree) 
[![Code Coverage](https://img.shields.io/codecov/c/github/nevadskiy/laravel-tree?token=9X6AQQYCPA)](https://packagist.org/packages/nevadskiy/laravel-tree)
[![Latest Stable Version](http://poser.pugx.org/nevadskiy/laravel-tree/v)](https://packagist.org/packages/nevadskiy/laravel-tree)
[![License](http://poser.pugx.org/nevadskiy/laravel-tree/license)](https://packagist.org/packages/nevadskiy/laravel-tree)

🌳 Tree-like structure for Eloquent models.

## ✅ Requirements

- Laravel 8+
- PostgreSQL and with "ltree" extension

## 🔌 Installation

Install the package via composer:

```bash
composer require nevadskiy/laravel-tree
````

Publish package migrations to create "ltree" extension (optional):

```bash
php artisan vendor:publish --tag=tree-migrations
```

## ✨ Introduction

To store hierarchical data structures in our application we can simply use the self-referencing `parent_id` column, and it will work fine in most cases.
However, when you have to make queries for such data, things get more complicated.

There is a simple solution to add an extra column to the table to save the path of the node in the hierarchy.
It's called a "materialized path" pattern and allows querying records more easily and efficiently.

PostgreSQL has a specific column type for that purpose called [ltree](https://www.postgresql.org/docs/current/ltree.html).
In combination with GiST index that allows executing lightweight and performant queries across an entire tree.
Also, PostgreSQL provides extensive facilities for searching through label trees.

Here is a simple example of how it works: 1st category "Books" is a parent of 2nd category "Science".

The database table in this scenario will look like this:

| id   | name     | parent_id | path |
|:-----|:---------|----------:|-----:|
| 1    | Books    |      null |    1 |
| 2    | Science  |         1 |  1.2 |

## 🔨 Configuration

Let's configure the package for nested categories.

Create a migration for the `categories` table:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->ltree('path')->nullable()->spatialIndex(); // Create a "path" column with a "ltree" type and a GiST index.
            $table->timestamps();
        });

        // Add a self-referencing "parent_id" column with a "foreign key" constraint using a separate database query.
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->index()
                ->constrained('categories')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

Now create the `Category` model.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nevadskiy\Tree\AsTree;

class Category extends Model
{
    use AsTree;
}
```

> Note that the Category model uses the `AsTree` trait.

## 🚊 Usage

### Path attribute

The `path` attribute is assigned to all models that use the `AsTree` trait **automatically** based on the `parent`, so you do not need to manually set it.

### Inserting models

A root node can be saved to the database very easily without extra effort:

```php
$root = new Category();
$root->name = 'Books';
$root->save();
```

To insert a child model, you only need to assign the `parent_id` attribute or use the `parent` relation like this:

```php
$child = new Category;
$child->name = 'Science';
$child->parent()->associate($root);
$child->save();
```

As you can see, it works as with regular Eloquent models.

### Relations

The `AsTree` trait provides the following relations:

- [`parent`](#parent)
- [`children`](#children)
- [`ancestors`](#ancestors) (read-only)
- [`descendants`](#descendants) (read-only)

The `parent` and `children` relations use default Laravel relations BelongsTo and HasMany.

The `ancestors` and `descendants` can be used only in the "read" mode, which means methods like `make` or `create` are not available. 
So to save related nodes you need to use the `parent` or `children` relation.

#### Parent

The `parent` relation uses the default Eloquent BelongsTo relation that needs the `parent_id` column as a foreign key.
It allows getting a parent of the node.

```php
echo $category->parent->name;
```

#### Children

The `children` relation uses a default Eloquent HasMany relation and is a reverse relation to the `parent`.
It allows getting all children of the node.

```php
foreach ($category->children as $child) {
    echo $child->name;
}
```

#### Ancestors

The `ancestors` relation is a custom relation that works only in "read" mode. 
It allows getting all ancestors of the node (without the current node).

Using the attribute:

```php
foreach ($category->ancestors as $ancestor) {
    echo $ancestor->name;
}
```

Using the query builder:

```php
$ancestors = $category->ancestors()->get();
```

Getting a collection with the current node and its ancestors:

```php
$hierarchy = $category->joinAncestors();
```

#### Descendants

The `descendants` relation is a custom relation that works only in "read" mode.
It allows getting all descendants of the node (without the current node).

Using the attribute:

```php
foreach ($category->descendants as $descendant) {
    echo $descendant->name;
}
```

Using the query builder:

```php
$ancestors = $category->descendants()->get();
```

### Querying models

Getting root nodes:

```php
$roots = Category::query()->root()->get(); 
```

Getting nodes by the depth level:

```php
$categories = Category::query()->whereDepth(3)->get(); 
```

Getting ancestors of the node (including the current node):

```php
$ancestors = Category::query()->whereSelfOrAncestorOf($category)->get();
```

Getting descendants of the node (including the current node):

```php
$ancestors = Category::query()->whereSelfOrDescendantOf($category)->get();
```

Ordering nodes by depth:

```php
$categories = Category::query()->orderByDepth()->get();
$categories = Category::query()->orderByDepthDesc()->get();
```

### HasManyDeep

The package provides the `HasManyDeep` relation that can be used to link, for example, a `Category` model that uses the `AsTree` trait with a `Product` model.

That allows us to get products of a category and each of its descendants.

Here is the code example on how to use the `HasManyDeep` relation:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nevadskiy\Tree\AsTree;
use Nevadskiy\Tree\Relations\HasManyDeep;

class Category extends Model
{
    use AsTree;

    public function products(): HasManyDeep
    {
        return HasManyDeep::between($this, Product::class);
    }
}
```

Now you can get products:

```php
$products = $category->products()->paginate(20);
```

### Querying category products

You can easily get the products of a category and each of its descendants using a query builder.

1st way:

```php
$products = Product::query()
    ->whereHas('category', function (Builder $query) use ($category) {
        $query->whereSelfOrDescendantOf($category);
    })
    ->paginate(25);
```

2nd way (faster, but requires an extra join):

```php
$products = Product::query()
    ->join('categories', function (JoinClause $join) {
        $join->on(
            Product::query()->qualifyColumn('category_id'),
            Category::query()->qualifyColumn('id')
        );
    })
    ->whereSelfOrDescendantOf($category);
    ->paginate(25, [
        Product::query()->qualifyColumn('*')
    ]);
```

### Moving nodes

When you move a node, the `path` column of the node and each of its descendants have to be updated as well.
Luckily the package does this automatically using a single query when it detects that the `parent_id` column has been updated.

So basically to move a node with its subtree you need to update the `parent` node of the current node:

```php
$books = Category::query()->where('name', 'Books')->firstOrFail();
$science = Category::query()->where('name', 'Science')->firstOrFail();

$science->parent()->associate($books);
$science->save();
```

### Other examples

#### Building a tree

To build a tree, we need to call the `tree` method on the `NodeCollection`:

```php
$tree = Category::query()->orderBy('name')->get()->tree();
```

This method associates nodes using the `children` relation and returns only root nodes.

#### Building breadcrumbs

```php
echo $category->joinAncestors()->reverse()->implode('name', ' > ');
```

#### Deleting a subtree

Delete the current node and all its descendants:

```php
$category->newQuery()->whereSelfOrDescendantOf($category)->delete();
```

## 📚 Useful links

- https://www.postgresql.org/docs/current/ltree.html
- https://patshaughnessy.net/2017/12/13/saving-a-tree-in-postgres-using-ltree
- https://patshaughnessy.net/2017/12/14/manipulating-trees-using-sql-and-the-postgres-ltree-extension

## ☕ Contributing

Thank you for considering contributing. Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for more information.

## 📜 License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.
