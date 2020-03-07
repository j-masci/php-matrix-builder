<?php

namespace JMasci\MatrixBuilder\Tests;

use JMasci\MatrixBuilder\Matrix;

Class MatrixTest extends \PHPUnit_Framework_TestCase {

    public static function get_test_matrix() {

        $m = new Matrix();

        // effortlessly break many tests with just one change below!
        $m->set( "r1", "c1", 11 );
        $m->set( "r1", "c2", 12 );
        $m->set( "r1", "c3", 13 );
        $m->set( "r2", "c1", 21 );
        $m->set( "r2", "c2", 22 );
        $m->set( "r2", "c3", 23 );
        $m->set( "r3", "c1", 31 );
        $m->set( "r3", "c2", 32 );
        $m->set( "r3", "c3", 33 );

        return $m;
    }

    public function testGet(){
        $m = self::get_test_matrix();
        $this->assertSame( 11, $m->get( "r1", "c1" ) );
    }

    public function testSet(){

        $m = self::get_test_matrix();
        $row = "r1";
        $col = "c1";
        $value = 55555;

        $m->set( $row, $col, $value );
        $this->assertSame( $value, $m->get( $row, $col ) );
    }

    public function testGetSetWithIntegerIndexes(){

        $m = new Matrix();

        $row = 1;
        $col = 2;
        $value = 55555;

        $m->set( $row, $col, $value );
        $this->assertSame( $value, $m->get( $row, $col ) );
    }

    public function testGetNonExistingCell() {
        $m = self::get_test_matrix();
        $this->assertEmpty( $m->get( "not_set_row", "not_set_column" ) );
    }

    public function testDeleteRow(){

        $m = self::get_test_matrix();

        // first, assert not empty
        $this->assertNotEmpty( $m->get_row( 'r3' ) );

        // then delete and assert empty
        $m->delete_row( 'r3' );
        $this->assertEquals( [], $m->get_row( 'r3' ) );
    }

    public function testDeleteColumn(){

        $m = self::get_test_matrix();

        // first, assert not empty
        $this->assertNotEmpty( $m->get_column( 'c3' ) );

        // then delete and assert empty
        $m->delete_column( 'c3' );
        $this->assertEquals( [], $m->get_row( 'c3' ) );
    }

    public function testDimensions(){
        $m = self::get_test_matrix();
        $this->assertSame( [ 3, 3 ], $m->get_dimensions() );
    }

    public function testApplyRowSort(){

        $m1 = self::get_test_matrix();

        $m1->apply_row_sort( [ 'r2', 'r3', 'r1' ] );

        // testing just the row keys
        $this->assertSame( [ 'r2', 'r3', 'r1' ], $m1->get_row_keys() );

        // testing an entire column, keys + values
        $this->assertSame( [
            "r2" => 21,
            "r3" => 31,
            "r1" => 11
        ], $m1->get_column( 'c1' ) );

    }

    public function testApplyColumnSort() {

        $m2 = self::get_test_matrix();

        $m2->apply_column_sort( [ 'c3', 'c2', 'c1' ] );

        $this->assertSame( [
            "c3" => 13,
            "c2" => 12,
            "c1" => 11
        ], $m2->get_row( 'r1' ) );
    }

    // tests that sort_rows() has the same effect as apply_row_sort()
    public function testRowSort() {

        $m1 = self::get_test_matrix();
        $m2 = clone $m1;

        $order = [ 'r3', 'r2', 'r1' ];

        // two things should have the same result:
        $m1->sort_rows( function( $keys ) use( $order ){
            return $order;
        });

        $m2->apply_row_sort( $order );

        $this->assertSame( $m1->get_matrix(), $m2->get_matrix() );
    }

    // tests that sort_columns() has the same effect as apply_column_sort()
    public function testSortColumns(){

        $m1 = self::get_test_matrix();
        $m2 = clone $m1;

        $order = [ 'c3', 'c2', 'c1' ];

        // two things should have the same result:
        $m1->sort_columns( function( $keys ) use( $order ){
            return $order;
        });

        $m2->apply_column_sort( $order );

        $this->assertSame( $m1->get_matrix(), $m2->get_matrix() );
    }

    public function testGetFirstRow() {

        $m = self::get_test_matrix();

        $this->assertSame( [
            "c1" => 11,
            "c2" => 12,
            "c3" => 13
        ], $m->get_first_row() );
    }

    public function testGetFirstColumn() {

        $m = self::get_test_matrix();

        $this->assertSame( [
            "r1" => 11,
            "r2" => 21,
            "r3" => 31
        ], $m->get_first_column() );
    }

    public function testSetRowTotals(){

        $m = new Matrix();

        $m->set( 1, 1, 50 );
        $m->set( 1, 2,  75 );

        $m->set( 2, 1, 9 );
        $m->set( 2, 2,  1 );

        // we're going to add a column with this name
        $col_name = "totals_column";

        // callback provided gets called once per column
        $m->set_row_totals( function( $row ){
            // intentionally use array indexes so that we fail if they are not
            // properly provided to us.
            return $row[2] - $row[1];
        }, $col_name );

        // 75 - 50
        $this->assertSame( 25, $m->get( 1, $col_name ) );

        // 1 - 9
        $this->assertSame( -8, $m->get( 2, $col_name ) );
    }

    public function testSetColumnTotals(){

        $m = new Matrix();

        $m->set( 1, 1, 20 );
        $m->set( 1, 2,  2 );

        $m->set( 2, 1, 30 );
        $m->set( 2, 2,  6 );

        // we're going to add a row with this name
        $row_key = "totals_row";

        // callback provided gets called once per row
        $m->set_column_totals( function( $column ){
            // intentionally use array indexes to ensure they are given properly
            return $column[2] - $column[1];
        }, $row_key );

        // 30 - 20
        $this->assertSame( 10, $m->get( $row_key, 1 ) );

        // 6 - 2
        $this->assertSame( 4, $m->get( $row_key, 2 ) );
    }

    public function testRecordSetWithHeadings(){

        $m = new Matrix();

        $m->set( "row_1", "col_1", 1 );
        $m->set( "row_1", "col_2",  4 );

        $m->set( "row_2", "col_1", 11 );
        $m->set( "row_2", "col_2",  100 );

        $row_labels = [
            "row_1" => "Row # 1",
            "row_2" => "Row # 2",
        ];

        $col_labels = [
            "col_1" => "Col # 1",
            "col_2" => "Col # 2"
        ];

        // the keys of the rows below are potentially not useful but this is what
        // we currently expect the function to return.
        $expected = [
            "row_heading" => [
                "column_heading" => 'origin',
                'col_1' => $col_labels["col_1"],
                'col_2' => $col_labels["col_2"],
            ],
            "row_1" => [
                "column_heading" => $row_labels["row_1"],
                'col_1' => 1,
                'col_2' => 4,
            ],
            "row_2" => [
                "column_heading" => $row_labels["row_2"],
                'col_1' => 11,
                'col_2' => 100
            ]
        ];

        $actual = $m->convert_to_record_set_with_headings( "origin", $row_labels, $col_labels, "row_heading", "column_heading" );

        $this->assertSame( $expected, $actual );

        // note: its debatable whether we should do this (below) because row keys in some cases
        // will not serve any purpose and could be numerically indexed instead. if the test
        // fails above, it might be ok to do this instead.
        // $this->assertSame( array_values( $expected ), array_values( $actual ) );
    }
}