# To Do List
- [ ] cover with tests
- [ ] prepare for release and publish on packagist
- [ ] add documentation
- [ ] add github actions
- [ ] feature descendants relation
- [ ] feature ancestors relation
- [ ] add `Tree` iterable class that has `NodeCollection` nodes on each level
- [ ] add `siblings` read-only relation.
- [ ] add `MySQL` driver support (based on `LIKE` operator) and determine by checkout model connection.
- [ ] add possibility to generate a whole tree using model factory. develop API to specify how many nodes should be created per a depth level / make it dynamic using callable syntax. probably use sequences.
- [ ] add possibility to restrict max depth level.
- [ ] add docs about `read-only` relations (descendants and ancestors). use `parent` and `children` for saving nodes.
- [ ] integrate position package behaviour for sorting.
- [ ] add missing methods and helpers.

# Links
- https://patshaughnessy.net/2017/12/13/saving-a-tree-in-postgres-using-ltree
- https://patshaughnessy.net/2017/12/14/manipulating-trees-using-sql-and-the-postgres-ltree-extension
- https://github.com/lazychaser/laravel-nestedset
- https://github.com/vicklr/materialized-model/blob/main/src/Traits/HasMaterializedPaths.php
- https://github.com/staudenmeir/laravel-adjacency-list
