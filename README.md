Dynamically builds (and modifies, sums, and sorts) a matrix and can export data for printing in a table that contains both row and column labels.

Internally the matrix is just an array of arrays (of equal length), but the object has methods to deal with both
rows and columns, because in general, row and column operations must be performed differently (normally, column
operations are much less trivial). The object let's you not worry about the differences and provides a column
method for every row method with the same name.

### Usage

```php
use JMasci\MatrixBuilder;

$matrix = new MatrixBuilder();

$matrix->set( 'row_1', 'col_1', 'value 1,1' );
$matrix->set( 'row_1', 'col_2', 'value 1,2' );
$matrix->set( 'row_2', 'col_1', 'value 2,1' );
$matrix->set( 'row_2', 'col_2', 'value 2,2' );

$matrix->get( 'row_1', 'col_2' );
// "value 1,2"

$matrix->get_column( 'col_1' );
// [ 'row_1' => 'value 1,1', 'row_2' => 'value 2,1' ]

$matrix->get_row( 'row_1' );
// [ 'col_1' => 'value 1,1', 'col_2' => 'value 1,2' ]

// put row_2 first.
$matrix->apply_row_sort( [ 'row_2' ]);

// put row_1 first again.
$matrix->apply_row_sort( [ 'row_1' ]);

// columns not found are ignored
$matrix->apply_column_sort( [ 'col_that_doesnt_exist', 'col_1' ]);

// or you can sort via callback (works for both rows/columns)
$matrix->sort_columns( function( $keys ) {
    asort( $keys );
    return $keys;
});

// which is just a shorter way of doing this...
$matrix->apply_column_sort( call_user_func( function( $keys ) {    
    asort( $keys );
    return $keys;
}, $matrix->get_column_keys() ));

// add another row (but using an existing column)
$matrix->set( 'row_3', 'col_1', 99 );

$matrix->get_row_keys(); 
// [ 'row_1', 'row_2', 'row_3 ]

$matrix->get_dimensions();
// [ 3, 2 ]

$matrix->get_column_keys(); 
// [ 'col_1', 'col_2' ] (same as before)

// sets row_3/col_3 to its old value + 5 or just 5.
$matrix->set( 'row_3', 'col_3', $matrix::get_incrementer( 5 ) );

// back to where we started...
$matrix->delete_row( 'row_3' );
$matrix->delete_column( 'col_3' );

// can do this too...
// $matrix->set_row_totals( 'some_reducer_function' );
// $matrix->set_column_totals( 'some_reducer_function' );

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

#### Example 2

This uses an incrementer function to add the number of posts that have the same author and post date. This 
will give similar results as running an SQL query and grouping by 2 columns and also selecting a count of 
the total.

```php
use JMasci\MatrixBuilder;

$matrix = new MatrixBuilder();

foreach ( query_posts_and_join_authors() as $post ) {
    // each time we call set we either add 1 to the previous value 
    // or initialize a new row and/or column and then add 1.
    $matrix->set( $post->author_name, date( 'm Y', strtotime( $post->post_date ) ), $matrix::get_incrementer(1));
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
$matrix->set_row_totals( $matrix::get_array_summer() );
$matrix->set_column_totals( $matrix::get_array_summer() );

echo your_own_table_rendering_functions( $matrix->convert_to_record_set_with_headings( "Authors vs. Post Dates") );
```

Renders a table such as...

| Authors vs. Post Dates | Author 1 | Author 2 | Author 3 | __Total |
|-----------------------|----------|----------|----------|---------|
| October 2019          | 3        | 4        | 2        | 9       |
| November 2019         | 12       | 2        | 5        | 19      |
| December 2019         | 5        | 3        | 0        | 8       |
| January 2020          | 22       | 7        | 15       | 44      |
| February 2020         | 0        | 0        | 0        | 0       |
| __Total               | 42       | 16       | 22       | 80      |

Possible Future Todo: Allow cells to use formula's as well as primitive values. Formula's can be
an issue if they involve circular dependencies. Ie. If cell D is the sum A, B, and C, but cell B 
is equal to cell D times two, then there's nothing we can do to resolve this.
Nevertheless, maybe there is a way we can catch these circular dependencies and not allow them to exist
in the first place, but, catching them is also not going to be easy.  