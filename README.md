A matrix in this context is an indexed array of indexed arrays. This library is a single class that lets you incrementally build such a matrix, and then offers some useful row and column methods such as simple getters/setters, deletion of entire rows or columns, and sorting of rows or columns. 

Is a class/encapsulation the best way to do this? Not sure.

Couldn't we just use primitive data structures and write pure functions? Probably. One of the reasons I used a class was that column operations usually end up being a lot more complex than row operations, for example, to delete a column, you loop through each row and delete the column from within each one. While deleting a row is a simple line of code. 

What I could have done was write a function to convert rows into columns (and column into rows). Then any function you write to operate on rows could be achieved on columns by calling the "inversion" function before and after the row operation (hope that makes sense). Anyways, for this library, all the functionality is wrapped up in a class which mostly encapsulates its data but does not prevent you from manipulating the internal data structure if you need to.

### Basic Usage

In general, every row method has a corresponding column method.

```php
use JMasci\MatrixBuilder;

// create a new empty matrix
$matrix = new MatrixBuilder();

// sets some values.
$matrix->set( 'row_1', 'col_1', 'value 1,1' );
$matrix->set( 'row_1', 'col_2', 'value 1,2' );
$matrix->set( 'row_2', 'col_1', 'value 2,1' );
$matrix->set( 'row_2', 'col_2', 'value 2,2' );

// returns "value 1,2"
$matrix->get( 'row_1', 'col_2' );

// returns [ 'row_1' => 'value 1,1', 'row_2' => 'value 2,1' ]
$matrix->get_column( 'col_1' );

// returns [ 'col_1' => 'value 1,1', 'col_2' => 'value 1,2' ]
$matrix->get_row( 'row_1' );

print_r( $matrix->get_matrix() );

```

```
gives you:

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

### Sorting, deleting, and settings totals

```php

// puts the given rows first. If you pass in a row that doesn't exist it will ignore it.
$matrix->apply_row_sort( [ 'row_2', 'row_1' ]);

// alternate sort method accepting an anonymous function. This would sort columns alphabetically.
$matrix->sort_rows( function( $keys ) {
    asort( $keys );
    return $keys;
});

// the set function also accepts an anonymous function, which will be provided the previous value.
// the value afterwards will be 100.
$matrix->set( 'row_1', 'col_1', 95 ); 
$matrix->set( 'row_1', 'col_1', $matrix::get_incrementer( 5 ) );

$matrix->delete_row( 'row_2' );

// adds a new column to each row whose value is determined by the callback function.
// useful when your values are numeric.
$matrix->set_row_totals( function( $row, $key ) { return array_sum( $row ); }, 'total' );

```

#### Real World Example

You could do some similar things in SQL using group by and count. But there's more flexibility when constructing the matrix in PHP.

```php
use JMasci\MatrixBuilder;

$matrix = new MatrixBuilder();

foreach ( query_posts_and_join_authors() as $post ) {
    // each time we call set we either add 1 to the previous value 
    // or initialize a new row and/or column and then add 1.
    $matrix->set( $post->author_name, date( 'm Y', strtotime( $post->post_date ) ), $matrix::get_incrementer(1));
}

// assume the query already sorted by date. Rows will remain sorted in the order that they were added. 

// sort authors by name
$matrix->sort_columns( function( $keys ){
    sort( $keys );
    return $keys;
});

$matrix->set_row_totals( $matrix::get_array_summer(), '__Total' );
$matrix->set_column_totals( $matrix::get_array_summer(), '__Total' );

$data = $matrix->convert_to_record_set_with_headings( "Authors vs. Post Dates");
```

Useful for rendering a table such as...

| Authors vs. Post Dates | Author 1 | Author 2 | Author 3 | __Total |
|-----------------------|----------|----------|----------|---------|
| October 2019          | 3        | 4        | 2        | 9       |
| November 2019         | 12       | 2        | 5        | 19      |
| December 2019         | 5        | 3        | 0        | 8       |
| January 2020          | 22       | 7        | 15       | 44      |
| February 2020         | 0        | 0        | 0        | 0       |
| __Total               | 42       | 16       | 22       | 80      |

Possible Future Addition: Allow cells to use formula's. This can get complicated though.
