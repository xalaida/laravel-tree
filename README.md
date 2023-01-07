[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner-direct-single.svg)](https://stand-with-ukraine.pp.ua)

# Laravel Tree

[![Test](https://img.shields.io/github/actions/workflow/status/nevadskiy/downloader/test.yml?branch=master)](https://packagist.org/packages/nevadskiy/laravel-tree)

[//]: # (TODO: add description)

## ✅ Requirements

- PHP 7.3 or newer

## 🔌 Installation

Install the package via composer.

```bash
composer require nevadskiy/laravel-tree
````

## 🔨 Usage

[//]: # (TODO: update documentation)

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
- https://github.com/vicklr/materialized-model/blob/main/src/Traits/HasMaterializedPaths.php
- https://github.com/staudenmeir/laravel-adjacency-list
