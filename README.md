[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner-direct-single.svg)](https://stand-with-ukraine.pp.ua)

# Laravel Tree

[//]: # (TODO: add other badges)
[![PHPUnit](https://img.shields.io/github/actions/workflow/status/nevadskiy/downloader/phpunit.yml?branch=master)](https://packagist.org/packages/nevadskiy/laravel-tree)

🌳 Hierarchy structure for Eloquent models.

## ✅ Requirements

- Laravel 8+
- PostgreSQL and with "ltree" extension

## 🔌 Installation

Install the package via composer.

```bash
composer require nevadskiy/laravel-tree
````

## 🔨 Introduction

To store the hierarchical data structures in our application we can simply use the `parent_id` column, and it will work fine in most cases.
However, when you have to make queries for such data, things get more complicated.

There is a simple solution to add an extra column to the table to save the node path in the hierarchy from the root. It's called a "materialized path" pattern and allows to query data more easily.

Here is a simple example how it works: 1st category "Books" is a parent of 2nd category "Science". The database table in this scenario will look like this:

| id  | name    | path |
|-----|---------|------|
| 1   | Books   | 1    |
| 2   | Science | 1.2  |


The PostgreSQL has a specific column type for that purpose called "ltree".

In combination with GiST index that allows to execute lightweight and performant queries across an entire tree.

Also, PostgreSQL has useful operators to select descendants of the node, ancestors, and a lot more.

More about the "ltree" extension: https://patshaughnessy.net/2017/12/13/saving-a-tree-in-postgres-using-ltree

## 🔨 Configuration

Let's configure package for nested categories.

Create a migration for `categories` table:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->ltree('path')->nullable()->spatialIndex();
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->after('name')
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

As you can see, we use a PostgreSQL `ltree` type for the `path` column.

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

The `path` attribute implements the "materialized path" pattern and stores the path of the node in the tree.


[//]: # (TODO: show example of stored `path` value)

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

The `path` attribute is **automatically** handled by the package, so you do not need to manually set it. 

As you can see, it works as default Eloquent models.

### Relations

The `AsTree` trait provides the following relations:

- parent
- children
- ancestors
- descendants

The `parent` and `children` relations use default Laravel's BelongsTo and HasMany relations. 

The `ancestors` and `descendants` can be used only in the "read" mode (method like `make`, `create` are not available).

### Querying

To select root nodes, use the `root` query scope:

```php
$roots = Category::query()->root()->get(); 
```

## ☕ Contributing

[//]: # (TODO: add contributing.md file)

Thank you for considering contributing. Please see [CONTRIBUTING](CONTRIBUTING.md) for more information.

## 📜 License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.


# Querying category products

[//]: # (TODO: split into 2 separate code blocks)
```php
Product::query()
    ->when($category, function (Builder $query, Category $category) {
        // 1st way (faster, harder)
        $query->joinRelation('category');
        $query->whereDescendantOf($category);

        // 2nd way (slower, simpler)
        $query->whereHas('category', function (Builder $query) use ($category) {
            $query->whereDescendantOf($category);
        });
    })
    ->paginate(25, [
        Product::query()->qualifyColumn('*') // Required for the 1st way
    ]);
```




# To Do List
- [ ] configure code coverage workflow & badge generation.
- [ ] configure cs fixer workflow.
- [ ] find better example for the doc introduction.
- [ ] configure changelog action (see: https://github.com/spatie/laravel-medialibrary/blob/main/.github/workflows/update-changelog.yml).
- [ ] test query relation without constraint (for example on `avg` methods).
- [ ] prepare for release and publish on packagist.
- [ ] add documentation.
- [ ] add possibility to use non-primary key column as source.
- [ ] consider adding `wherePath` method that allow to use raw ltree queries (https://www.postgresql.org/docs/current/ltree.html).
- [ ] add `Tree` iterable class that has `NodeCollection` nodes on each level.
- [ ] add `siblings` read-only relation.
- [ ] add `MySQL` driver support (based on `LIKE` operator) and determine by checkout model connection.
- [ ] add possibility to generate a whole tree using model factory. develop API to specify how many nodes should be created per a depth level / make it dynamic using callable syntax. probably use sequences.
- [ ] add possibility to restrict max depth level.
- [ ] add docs about `read-only` relations (descendants and ancestors). use `parent` and `children` for saving nodes.
- [ ] check integration with position package.
- [ ] add missing methods and helpers.

# Links
- https://patshaughnessy.net/2017/12/13/saving-a-tree-in-postgres-using-ltree
- https://patshaughnessy.net/2017/12/14/manipulating-trees-using-sql-and-the-postgres-ltree-extension
- https://github.com/lazychaser/laravel-nestedset
- https://github.com/staudenmeir/laravel-adjacency-list
  https://github.com/vicklr/materialized-model
