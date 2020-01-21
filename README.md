Dynamically builds (and modifies, sums, and sorts) a matrix and can export data for printing in a table that contains both row and column labels.

### Usage

```php
use JMasci\MatrixBuilder;

$matrix = new MatrixBuilder();

$matrix->set( 'row_1', 'col_1', 'value 1,1' );
$matrix->set( 'row_1', 'col_2', 'value 1,2' );
$matrix->set( 'row_2', 'col_1', 'value 2,1' );
$matrix->set( 'row_2', 'col_2', 'value 2,2' );

print_r( $matrix->get_matrix() );
```

Results in:

```
Array
(
    [row_1] => Array
        (
            [col_1] => value 1,1
            [col_2] => value 1,2
        )

    [row_2] => Array
        (
            [col_1] => value 2,1
            [col_2] => value 2,2
        )

)
```

#### More Complex Example

This uses an incrementer function to add the number of posts that have the same author and post date. This 
will give similar results as running an SQL query and grouping by 2 columns and also selecting a count of 
the total.

```php
$matrix = new MatrixBuilder();

foreach ( query_posts_and_join_authors() as $post ) {
    // each time we call set we either add 1 to the previous value 
    // or initialize a new row and/or column and then add 1.
    $matrix->set( $post->author_name, date( 'm Y', strtotime( $post->post_date ) ), get_incrementer(1));
}

// sort keys alphabetically
$matrix->sort_rows( function( $keys ){
    asort( $keys );
    return $keys;
});

// sort cols
$matrix->sort_columns( function( $keys ){
    sort( $keys, SORT_NUMERIC );
    return $keys;
});

// we can add cells that sum the values of all rows/columns.
$matrix->set_row_totals( $matrix::get_column_adder() );
$matrix->set_column_totals( $matrix::get_column_adder() );

echo some_table_rendering_function( $matrix->convert_to_record_set_with_headings( "Authors vs. Post Dates") );
```

This might generate something like...

| Authors vs. Post Dates | Author 1 | Author 2 | Author 3 | __Total |
|-----------------------|----------|----------|----------|---------|
| October 2019          | 3        | 4        | 2        | 9       |
| November 2019         | 12       | 2        | 5        | 19      |
| December 2019         | 5        | 3        | 0        | 8       |
| January 2020          | 22       | 7        | 15       | 44      |
| February 2020         | 0        | 0        | 0        | 0       |
| __Total               | 42       | 16       | 22       | 80      |

Possible Future Todo: Allow cells to use formula's as well as primitive values. A single formula isn't
so much of an issue but when we allow generic cells to be formula's and logically depend on any
other cells values, then we run into potential circular dependency issues which might be pretty
hard (impossible) to deal with? I'm pretty sure it's impossible actually. If cell D is the sum
of A, B, and C, but cell B is equal to cell D times two, then there's nothing we can do to resolve this.
Nevertheless, maybe there is a way we can catch these circular dependencies and not allow them to exist
in the first place. The process of catching them also does not seem easy. 