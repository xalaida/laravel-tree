[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner-direct-single.svg)](https://stand-with-ukraine.pp.ua)

# Laravel Tree

[//]: # (TODO: add other badges)
[![PHPUnit](https://img.shields.io/github/actions/workflow/status/nevadskiy/downloader/phpunit.yml?branch=master)](https://packagist.org/packages/nevadskiy/laravel-tree)

🌳 Tree-like structure for Eloquent models.

## ✅ Requirements

- Laravel 8+
- PostgreSQL and with "ltree" extension

## 🔌 Installation

Install the package via composer.

```bash
composer require nevadskiy/laravel-tree
````

## ✨ Introduction

To store the hierarchical data structures in our application we can simply use the `parent_id` column, and it will work fine in most cases.
However, when you have to make queries for such data, things get more complicated.
There is a simple solution to add an extra column to the table to keep the path of the node in the tree-like hierarchy. 
It's called a "materialized path" pattern and allows to query data more easily and efficient.

Here is a simple example how it works: 1st category "Books" is a parent of 2nd category "Science". 
The database table in this scenario will look like this:

| id  | name    | path |
|-----|---------|------|
| 1   | Books   | 1    |
| 2   | Science | 1.2  |

The PostgreSQL has a specific column type for that purpose called "ltree".
In combination with GiST index that allows to execute lightweight and performant queries across an entire tree.

Also, PostgreSQL provides extensive facilities for searching through label trees.

## 🔨 Configuration

Let's configure package for nested categories.

Create a migration for `categories` table:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->ltree('path')->nullable()->spatialIndex(); // Create a "path" column with a "ltree" type and a GiST index.
            $table->timestamps();
        });

        // Add a self-referenced "parent_id" column with a "foreign key" constraint using a separate database query.
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->index()
                ->constrained('categories')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

Now, create the `Category` model.

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

## 🚊 Usage

### Path attribute

The "path" attribute is assigned to all models that use the `AsTree` trait **automatically**, so you do not need to manually set it.

[//]: # (TODO: add info that path of whole subtree is updated when node is moved)

### Inserting models

A root node can be saved to database very easy without extra effort:

```php
$root = new Category();
$root->name = 'Books';
$root->save();
```

To insert a child model, you only need to assign `parent_id` attribute or use the `parent` relation like this:

```php
$child = new Category;
$child->name = 'Science';
$child->parent()->associate($root);
$child->save();
```

As you can see, it works as with regular Eloquent models.

### Relations

The `AsTree` trait provides the following relations:

- `parent`
- `children`
- `ancestors` (read-only)
- `descendants` (read-only)

The `parent` and `children` relations use default Laravel relations BelongsTo and HasMany.

The `ancestors` and `descendants` can be used only in the "read" mode, which means methods like `make`, `create` are not available, so to save related nodes you need to use `parent` and `children` relations.

#### Parent

The `parent` relation uses default Eloquent BelongsTo relation that needs the `parent_id` column as foreign key.
It allows to get a parent of the node.

##### Example

```php
echo $category->parent->name; // 'Books'
```

#### Children

The `children` relation uses a default Eloquent HasMany relation and is a reverse relation to the `parent`.
It allows to get all children of the node.

##### Example

```php
foreach ($category->children as $child) {
    echo $child->name;
}
```

#### Ancestors

The `ancestors` relation is a custom relation that works only in "read" mode. 
It allows to get all ancestors of the node.

##### Example

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

Building breadcrumbs:

```php
collect()->join()
echo $category->ancestors()
    ->orderByDepthDesc()
    ->get()
    ->push($category)
    ->implode('name', ' > ');
```

#### Descendants

The `descendants` relation is a custom relation that works only in "read" mode.
It allows to get all descendants of the node.

##### Example

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

Getting ancestors of the node:

```php
$ancestors = Category::query()->whereAncestorOf($category)->get();
```

Getting descendants of the node:

```php
$ancestors = Category::query()->whereDescendantOf($category)->get();
```

Ordering nodes by depth:

```php
$categories = Category::query()->orderByDepth()->get();
$categories = Category::query()->orderByDepthDesc()->get();
```

### Querying category products

You can easily get the products of a category and each of its descendants.

1st way:

```php
$products = Product::query()
    ->whereHas('category', function (Builder $query) use ($category) {
        $query->whereDescendantOf($category);
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
    ->whereDescendantOf($category);
    ->paginate(25, [Product::query()->qualifyColumn('*')]);
```

### Moving nodes

When you move a node, the `path` column of the node and each of its descendant has to be updated as well.
Luckily the package does this automatically using a single query, when it detects that the `parent_id` column has been updated.

So basically to move a whole subtree you need to update the `parent` of the root node of the subtree:

```php
$books = Category::query()->where('name', 'Books')->firstOrFail();
$science = Category::query()->where('name', 'Science')->firstOrFail();

$science->parent()->associate($books);
$science->save();
```

## ☕ Contributing

[//]: # (TODO: add contributing.md file)

Thank you for considering contributing. Please see [CONTRIBUTING](CONTRIBUTING.md) for more information.

## 📜 License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.

## Useful links

- https://www.postgresql.org/docs/current/ltree.html
- https://patshaughnessy.net/2017/12/13/saving-a-tree-in-postgres-using-ltree
- https://patshaughnessy.net/2017/12/14/manipulating-trees-using-sql-and-the-postgres-ltree-extension
